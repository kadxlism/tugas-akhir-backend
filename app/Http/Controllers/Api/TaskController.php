<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Services\TimeLogService;

class TaskController extends Controller
{
    public function __construct(
        protected TimeLogService $timeLogService
    ) {}
    /**
     * List semua task untuk user saat ini dengan pagination
     */
    public function listForCurrentUser(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 5); // Default 5 items per page
        $page = $request->get('page', 1);
        $search = $request->get('search', '');

        $query = Task::with(['assignee', 'project'])
            ->orderBy('created_at', 'desc'); // Tugas terbaru di atas

        // Jika bukan admin atau CS, hanya tampilkan task milik user
        if ($user->role !== 'admin' && $user->role !== 'customer_service') {
            $query->where('assigned_to', $user->id);
        }

        // Tambahkan search jika ada
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhere('priority', 'LIKE', "%{$search}%")
                  ->orWhere('paket', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $tasks->items(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
            'per_page' => $tasks->perPage(),
            'total' => $tasks->total(),
            'from' => $tasks->firstItem(),
            'to' => $tasks->lastItem(),
            'search' => $search, // Return search term untuk frontend
        ]);
    }

    /**
     * Membuat task baru (khusus admin & customer_service)
     */
    public function createByRole(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'customer_service'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'paket' => 'nullable|string|max:255', // ✅ kolom baru
            'category' => 'nullable|string|max:255', // ✅ kolom kategori
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'status' => 'in:todo,in_progress,review,done',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high',
        ]);

        // Auto-populate paket and category from Project/Client if not provided
        if (empty($data['paket']) || empty($data['category'])) {
            $project = Project::with('clientData')->find($data['project_id']);

            if ($project && $project->clientData) {
                // Auto-fill paket from client if not provided
                if (empty($data['paket']) && $project->clientData->package) {
                    $data['paket'] = $project->clientData->package;
                }

                // Auto-fill category from client if not provided
                if (empty($data['category']) && $project->clientData->category) {
                    $data['category'] = $project->clientData->category;
                }
            }
        }

        $task = Task::create($data);
        $task->load(['assignee', 'project']);

        return response()->json($task, 201);
    }

    /**
     * Update task berdasarkan role
     */
    public function updateByRole(Request $request, Task $task)
    {
        $user = Auth::user();

        // Debug: Log request data
        Log::info('Update task request data:', $request->all());

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'paket' => 'sometimes|nullable|string|max:255', // ✅ kolom baru
            'category' => 'sometimes|nullable|string|max:255', // ✅ kolom kategori
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:todo,in_progress,review,done',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'due_date' => 'sometimes|date|nullable',
            'priority' => 'in:low,medium,high',
        ]);

        // Jika bukan admin, tapi dia adalah assignee, boleh ubah status dan deskripsi
        if ($user->role !== 'admin' && $user->role !== 'customer_service' && $user->id === $task->assigned_to) {
            // Hapus category dari data jika ada (keamanan tambahan)
            unset($data['category']);
            $allowedFields = array_intersect_key($data, array_flip(['status', 'description']));
            Log::info('User updating task with allowed fields:', $allowedFields);

            // Check if status is being changed to "done"
            if (isset($allowedFields['status']) && $allowedFields['status'] === 'done' && $task->status !== 'done') {
                $task->update($allowedFields);
                $this->stopTimersForTask($task->id);
            } elseif (isset($allowedFields['status']) && $allowedFields['status'] === 'in_progress' && $task->status !== 'in_progress') {
                $task->update($allowedFields);
                $this->startTimerForTask($task->id);
            } else {
                $task->update($allowedFields);
            }

            return response()->json($task);
        }

        // Admin atau CS bisa ubah semua field termasuk category
        if (in_array($user->role, ['admin', 'customer_service'])) {
            Log::info('Updating task with data:', $data);

            // Check if status is being changed to "done"
            if (isset($data['status']) && $data['status'] === 'done' && $task->status !== 'done') {
                $task->update($data);
                $this->stopTimersForTask($task->id);
            } elseif (isset($data['status']) && $data['status'] === 'in_progress' && $task->status !== 'in_progress') {
                $task->update($data);
                $this->startTimerForTask($task->id);
            } else {
                $task->update($data);
            }

            Log::info('Task updated successfully');
            return response()->json($task);
        }

        // Jika user bukan admin/CS dan bukan assignee, hapus category dari data
        unset($data['category']);

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Update status task
     */
    public function updateStatus(Request $request, Task $task)
    {
        $user = Auth::user();

        $data = $request->validate([
            'status' => 'required|in:todo,in_progress,review,done',
        ]);

        if ($user->role === 'admin' || $user->role === 'customer_service' || $user->id === $task->assigned_to) {
            $oldStatus = $task->status;
            $task->update(['status' => $data['status']]);

            // Auto-stop timers if task becomes "Done"
            if ($data['status'] === 'done' && $oldStatus !== 'done') {
                $this->stopTimersForTask($task->id);
            }

            // Auto-start timer if task becomes "In Progress"
            if ($data['status'] === 'in_progress' && $oldStatus !== 'in_progress') {
                $this->startTimerForTask($task->id);
            }

            return response()->json($task);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Start timer for a task (auto-stop existing if any)
     */
    private function startTimerForTask($taskId)
    {
        try {
            // Check for existing active timer for current user
            $activeTimer = $this->timeLogService->getActiveTimer();
            if ($activeTimer) {
                $this->timeLogService->stopTimer($activeTimer->id);
            }

            // Start new timer
            $this->timeLogService->startTimer($taskId);
        } catch (\Exception $e) {
            Log::error('Failed to auto-start timer for task ' . $taskId . ': ' . $e->getMessage());
        }
    }

    /**
     * Stop all active timers for a task
     */
    private function stopTimersForTask($taskId)
    {
        $activeTimers = TimeLog::where('task_id', $taskId)
            ->whereNull('end_time')
            ->get();

        foreach ($activeTimers as $timer) {
            try {
                $this->timeLogService->stopTimer($timer->id);
            } catch (\Exception $e) {
                Log::error('Failed to auto-stop timer for task ' . $taskId . ': ' . $e->getMessage());
            }
        }
    }









    /**
     * Hapus task dengan role-based access
     */
    public function destroyByRole(Task $task)
    {
        $user = Auth::user();

        // Hanya admin atau customer_service yang bisa hapus task
        if (!in_array($user->role, ['admin', 'customer_service'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $taskTitle = $task->title;
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
            'task_title' => $taskTitle
        ]);
    }


}
