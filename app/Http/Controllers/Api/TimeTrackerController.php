<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimeLog;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TimeLogExport;

class TimeTrackerController extends Controller
{
    /**
     * Get all time logs with filters
     */
    public function index(Request $request)
    {
        $query = TimeLog::with(['user', 'task']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by project (via task relationship)
        if ($request->has('project_id')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by task
        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        // Daily timesheet
        if ($request->has('daily') && $request->daily) {
            $date = $request->date ?? Carbon::today();
            $query->whereDate('start_time', $date);
        }

        // Weekly timesheet
        if ($request->has('weekly') && $request->weekly) {
            $weekStart = $request->week_start ?? Carbon::now()->startOfWeek();
            $weekEnd = $request->week_end ?? Carbon::now()->endOfWeek();
            $query->whereBetween('start_time', [$weekStart, $weekEnd]);
        }

        $timeLogs = $query->orderBy('start_time', 'desc')->paginate($request->per_page ?? 50);

        return response()->json($timeLogs);
    }

    /**
     * Get active timer for current user
     */
    public function getActiveTimer()
    {
        $activeTimer = TimeLog::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->where('status', 'pending')
            ->with(['task', 'user'])
            ->first();

        if ($activeTimer) {
            // Check if task is still "In Progress"
            if ($activeTimer->task->status !== 'in_progress') {
                // Auto-stop timer if task is not in progress
                $this->stopTimer($activeTimer->id);
                return response()->json(['message' => 'Timer stopped automatically. Task is not in progress.'], 200);
            }

            // Calculate current duration in minutes
            $startTime = Carbon::parse($activeTimer->start_time);
            $now = Carbon::now();
            $elapsed = $now->diffInMinutes($startTime) - $activeTimer->paused_duration_minutes;

            $activeTimer->current_duration_minutes = $elapsed;
            $activeTimer->is_paused = !is_null($activeTimer->paused_at);
        }

        return response()->json($activeTimer);
    }

    /**
     * Start timer for a task
     */
    public function startTimer(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'notes' => 'nullable|string',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        // Check if task is "In Progress"
        if ($task->status !== 'in_progress') {
            return response()->json([
                'message' => 'Timer can only be started for tasks with status "In Progress"'
            ], 400);
        }

        // Check if user already has an active timer
        $activeTimer = TimeLog::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        if ($activeTimer) {
            return response()->json([
                'message' => 'You already have an active timer. Please stop it first.'
            ], 400);
        }

        $timer = TimeLog::create([
            'user_id' => Auth::id(),
            'task_id' => $validated['task_id'],
            'start_time' => Carbon::now(),
            'note' => $validated['notes'] ?? null,
            'status' => 'pending',
            'duration_minutes' => 0, // Will be calculated when stopped
        ]);

        return response()->json($timer->load(['task', 'user']), 201);
    }

    /**
     * Pause timer
     */
    public function pauseTimer($id)
    {
        $timer = TimeLog::where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->firstOrFail();

        if ($timer->paused_at) {
            return response()->json([
                'message' => 'Timer is already paused'
            ], 400);
        }

        $timer->update([
            'paused_at' => Carbon::now(),
        ]);

        return response()->json($timer->load(['task', 'user']));
    }

    /**
     * Resume timer
     */
    public function resumeTimer($id)
    {
        $timer = TimeLog::where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->firstOrFail();

        if (!$timer->paused_at) {
            return response()->json([
                'message' => 'Timer is not paused'
            ], 400);
        }

        // Calculate paused duration in minutes
        $pausedAt = Carbon::parse($timer->paused_at);
        $resumeAt = Carbon::now();
        $pausedDuration = $resumeAt->diffInMinutes($pausedAt);

        $timer->update([
            'paused_duration_minutes' => $timer->paused_duration_minutes + $pausedDuration,
            'paused_at' => null,
        ]);

        return response()->json($timer->load(['task', 'user']));
    }

    /**
     * Stop timer
     */
    public function stopTimer($id)
    {
        $timer = TimeLog::where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->firstOrFail();

        $startTime = Carbon::parse($timer->start_time);
        $endTime = Carbon::now();

        // Calculate total duration in minutes
        $totalDuration = $endTime->diffInMinutes($startTime);

        // If paused, add the remaining paused time
        if ($timer->paused_at) {
            $pausedAt = Carbon::parse($timer->paused_at);
            $pausedDuration = $endTime->diffInMinutes($pausedAt);
            $timer->paused_duration_minutes += $pausedDuration;
            $timer->paused_at = null;
        }

        // Net duration = total - paused
        $netDuration = max(0, $totalDuration - $timer->paused_duration_minutes);

        $timer->update([
            'end_time' => $endTime,
            'duration_minutes' => $netDuration,
        ]);

        return response()->json($timer->load(['task', 'user']));
    }

    /**
     * Create manual time entry
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'nullable|integer|min:0',
            'note' => 'nullable|string',
        ]);

        $task = Task::findOrFail($validated['task_id']);

        // Calculate duration in minutes if not provided
        $startDateTime = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $endDateTime = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        $durationMinutes = $validated['duration_minutes'] ?? $endDateTime->diffInMinutes($startDateTime);

        $timer = TimeLog::create([
            'user_id' => Auth::id(),
            'task_id' => $validated['task_id'],
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'duration_minutes' => $durationMinutes,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($timer->load(['task', 'user']), 201);
    }

    /**
     * Get total time per task
     */
    public function getTotalTimePerTask($taskId)
    {
        $totalDuration = TimeLog::where('task_id', $taskId)
            ->where('status', 'approved')
            ->sum('duration_minutes');

        $totalPaused = TimeLog::where('task_id', $taskId)
            ->where('status', 'approved')
            ->sum('paused_duration_minutes');

        $netDuration = $totalDuration - $totalPaused;

        $hours = floor($netDuration / 60);
        $minutes = $netDuration % 60;

        // Update task's actual_time_minutes
        $task = Task::findOrFail($taskId);
        $task->update(['actual_time_minutes' => $netDuration]);

        return response()->json([
            'task_id' => $taskId,
            'total_minutes' => $netDuration,
            'formatted' => sprintf('%02d:%02d', $hours, $minutes),
            'hours' => $hours,
            'minutes' => $minutes,
        ]);
    }

    /**
     * Get timesheet (daily or weekly)
     */
    public function getTimesheet(Request $request)
    {
        $query = TimeLog::with(['user', 'task'])
            ->where('user_id', $request->user_id ?? Auth::id());

        if ($request->has('type')) {
            if ($request->type === 'daily') {
                $date = $request->date ?? Carbon::today();
                $query->whereDate('start_time', $date);
            } elseif ($request->type === 'weekly') {
                $weekStart = $request->week_start ?? Carbon::now()->startOfWeek();
                $weekEnd = $request->week_end ?? Carbon::now()->endOfWeek();
                $query->whereBetween('start_time', [$weekStart, $weekEnd]);
            }
        }

        if ($request->has('project_id')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        $timeLogs = $query->orderBy('start_time', 'asc')->get();

        // Group by date for better display
        $grouped = $timeLogs->groupBy(function ($log) {
            return Carbon::parse($log->start_time)->format('Y-m-d');
        });

        $summary = [
            'total_duration' => $timeLogs->sum(function ($log) {
                return $log->duration_minutes - $log->paused_duration_minutes;
            }),
            'total_logs' => $timeLogs->count(),
            'by_date' => $grouped->map(function ($logs, $date) {
                return [
                    'date' => $date,
                    'total_duration' => $logs->sum(function ($log) {
                        return $log->duration_minutes - $log->paused_duration_minutes;
                    }),
                    'logs' => $logs,
                ];
            }),
        ];

        return response()->json([
            'time_logs' => $timeLogs,
            'summary' => $summary,
        ]);
    }

    /**
     * Approve time log (Admin/PM only)
     */
    public function approve($id, Request $request)
    {
        Gate::authorize('approve', TimeLog::class);

        $timeLog = TimeLog::findOrFail($id);

        if ($timeLog->status === 'approved') {
            return response()->json([
                'message' => 'Time log is already approved'
            ], 400);
        }

        $timeLog->update([
            'status' => 'approved',
        ]);

        // Update task's actual_time_minutes
        $task = Task::findOrFail($timeLog->task_id);
        $totalMinutes = TimeLog::where('task_id', $timeLog->task_id)
            ->where('status', 'approved')
            ->sum(function ($log) {
                return $log->duration_minutes - $log->paused_duration_minutes;
            });
        $task->update(['actual_time_minutes' => $totalMinutes]);

        return response()->json($timeLog->load(['task', 'user']));
    }

    /**
     * Reject time log (Admin/PM only)
     */
    public function reject($id, Request $request)
    {
        Gate::authorize('approve', TimeLog::class);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $timeLog = TimeLog::findOrFail($id);

        if ($timeLog->status === 'rejected') {
            return response()->json([
                'message' => 'Time log is already rejected'
            ], 400);
        }

        $timeLog->update([
            'status' => 'rejected',
            'note' => ($timeLog->note ? $timeLog->note . "\n\n" : '') . 'Rejection reason: ' . $validated['rejection_reason'],
        ]);

        return response()->json($timeLog->load(['task', 'user']));
    }

    /**
     * Show single time log
     */
    public function show(TimeLog $timeLog)
    {
        return response()->json($timeLog->load(['user', 'task']));
    }

    /**
     * Update time log
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        // Only allow updates if status is pending
        if ($timeLog->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending time logs can be updated'
            ], 400);
        }

        // Only allow user to update their own time logs
        if ($timeLog->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only update your own time logs'
            ], 403);
        }

        $validated = $request->validate([
            'start_time' => 'sometimes|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'duration_minutes' => 'sometimes|integer|min:0',
            'note' => 'nullable|string',
        ]);

        $timeLog->update($validated);

        return response()->json($timeLog->load(['task', 'user']));
    }

    /**
     * Delete time log
     */
    public function destroy(TimeLog $timeLog)
    {
        // Only allow deletion if status is pending
        if ($timeLog->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending time logs can be deleted'
            ], 400);
        }

        // Only allow user to delete their own time logs
        if ($timeLog->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only delete your own time logs'
            ], 403);
        }

        $timeLog->delete();

        return response()->json(null, 204);
    }

    /**
     * Export time logs
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv'); // csv, xlsx, pdf

        $query = TimeLog::with(['user', 'task']);

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('project_id')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }
        if ($request->has('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $timeLogs = $query->orderBy('start_time', 'desc')->get();

        $filename = 'time_logs_' . Carbon::now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            // For PDF export, we'll use a simple HTML response or require dompdf package
            // For now, redirect to Excel export
            return Excel::download(new TimeLogExport($timeLogs), $filename . '.xlsx');
        } else {
            return Excel::download(new TimeLogExport($timeLogs), $filename . '.' . $format);
        }
    }

    /**
     * Check for long-running timers and send reminders
     */
    public function checkLongRunningTimers()
    {
        $maxHours = 8; // Default max hours before reminder
        $cutoffTime = Carbon::now()->subHours($maxHours);

        $longRunningTimers = TimeLog::whereNull('end_time')
            ->where('start_time', '<', $cutoffTime)
            ->with(['user', 'task'])
            ->get();

        $reminders = [];
        foreach ($longRunningTimers as $timer) {
            $hours = Carbon::parse($timer->start_time)->diffInHours(Carbon::now());
            $reminders[] = [
                'timer_id' => $timer->id,
                'user_id' => $timer->user_id,
                'user_name' => $timer->user->name ?? 'N/A',
                'task_id' => $timer->task_id,
                'task_title' => $timer->task->title ?? 'N/A',
                'hours_running' => $hours,
                'start_time' => $timer->start_time,
            ];
        }

        return response()->json([
            'long_running_timers' => $reminders,
            'count' => count($reminders),
        ]);
    }
}
