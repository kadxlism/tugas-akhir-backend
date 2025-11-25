<?php

namespace App\Services;

use App\Models\TimeLog;
use App\Models\Task;
use App\Events\LongRunningTimerEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeLogService
{
    /**
     * Start a timer for a task
     */
    public function startTimer(string $taskId, ?string $note = null): TimeLog
    {
        $task = Task::findOrFail($taskId);

        // Check if task is "In Progress"
        if ($task->status !== 'in_progress') {
            throw new \Exception('Timer can only be started for tasks with status "In Progress"');
        }

        // Check if user already has an active timer
        $activeTimer = $this->getActiveTimer();
        if ($activeTimer) {
            throw new \Exception('You already have an active timer. Please stop it first.');
        }

        return TimeLog::create([
            'user_id' => Auth::id(),
            'task_id' => $taskId,
            'start_time' => Carbon::now(),
            'note' => $note,
            'status' => 'pending',
            'duration_minutes' => 0,
            'source' => 'timer',
        ]);
    }

    /**
     * Pause an active timer
     */
    public function pauseTimer(string $timerId): TimeLog
    {
        $timer = $this->getTimerForUser($timerId);

        if ($timer->paused_at) {
            throw new \Exception('Timer is already paused');
        }

        $timer->update([
            'paused_at' => Carbon::now(),
        ]);

        return $timer->fresh();
    }

    /**
     * Resume a paused timer
     */
    public function resumeTimer(string $timerId): TimeLog
    {
        $timer = $this->getTimerForUser($timerId);

        if (!$timer->paused_at) {
            throw new \Exception('Timer is not paused');
        }

        // Calculate paused duration in minutes
        $pausedAt = Carbon::parse($timer->paused_at);
        $resumeAt = Carbon::now();
        $pausedDuration = $resumeAt->diffInMinutes($pausedAt);

        $timer->update([
            'paused_duration_minutes' => $timer->paused_duration_minutes + $pausedDuration,
            'paused_at' => null,
        ]);

        return $timer->fresh();
    }

    /**
     * Stop an active timer and calculate duration
     */
    public function stopTimer(string $timerId): TimeLog
    {
        $timer = $this->getTimerForUser($timerId);

        $startTime = Carbon::parse($timer->start_time);
        $endTime = Carbon::now();

        // Calculate total duration in seconds
        $totalSeconds = abs($endTime->diffInSeconds($startTime));

        // Calculate paused duration in seconds (approximate from stored minutes)
        $pausedSeconds = $timer->paused_duration_minutes * 60;

        // If paused, add the remaining paused time
        if ($timer->paused_at) {
            $pausedAt = Carbon::parse($timer->paused_at);
            $currentPausedSeconds = abs($endTime->diffInSeconds($pausedAt));
            $pausedSeconds += $currentPausedSeconds;
            
            // Update stored paused duration (round to nearest minute for consistency)
            $timer->paused_duration_minutes = (int) round($pausedSeconds / 60);
            $timer->paused_at = null;
        }

        // Net duration = total - paused
        $netSeconds = max(0, $totalSeconds - $pausedSeconds);
        $netMinutes = (int) ceil($netSeconds / 60);

        // Ensure at least 1 minute if there was any duration but it rounded down (though ceil handles 0.1 -> 1)
        // If netSeconds is 0, netMinutes is 0.
        if ($netMinutes === 0) {
            $netMinutes = 1;
        }

        $timer->update([
            'end_time' => $endTime,
            'duration_minutes' => $netMinutes,
            'paused_duration_minutes' => $timer->paused_duration_minutes,
        ]);

        // Update task's actual_time_minutes if status is approved
        if ($timer->status === 'approved') {
            $this->updateTaskActualTime($timer->task_id);
        }

        return $timer->fresh();
    }

    /**
     * Create manual time entry
     */
    public function createManualEntry(array $data): TimeLog
    {
        $task = Task::findOrFail($data['task_id']);

        // Calculate duration in minutes
        $startDateTime = Carbon::parse($data['date'] . ' ' . $data['start_time']);
        $endDateTime = Carbon::parse($data['date'] . ' ' . $data['end_time']);
        $durationMinutes = $data['duration_minutes'] ?? $endDateTime->diffInMinutes($startDateTime);

        $timeLog = TimeLog::create([
            'user_id' => Auth::id(),
            'task_id' => $data['task_id'],
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'duration_minutes' => $durationMinutes,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'source' => 'manual',
        ]);

        // Update task's actual_time_minutes if status is approved
        if ($timeLog->status === 'approved') {
            $this->updateTaskActualTime($timeLog->task_id);
        }

        return $timeLog;
    }

    /**
     * Get active timer for current user
     */
    public function getActiveTimer(): ?TimeLog
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
                return null;
            }

            // Calculate current duration in minutes
            $startTime = Carbon::parse($activeTimer->start_time);
            $now = Carbon::now();
            $elapsed = $now->diffInMinutes($startTime) - $activeTimer->paused_duration_minutes;

            $activeTimer->current_duration_minutes = $elapsed;
            $activeTimer->is_paused = !is_null($activeTimer->paused_at);
        }

        return $activeTimer;
    }

    /**
     * Get time logs for a task
     */
    public function getTimeLogsForTask(string $taskId): array
    {
        $timeLogs = TimeLog::where('task_id', $taskId)
            ->with(['user', 'task'])
            ->orderBy('start_time', 'desc')
            ->get();

        $totalMinutes = $timeLogs->where('status', 'approved')
            ->sum(function ($log) {
                return $log->duration_minutes;
            });

        return [
            'time_logs' => $timeLogs,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
        ];
    }

    /**
     * Get timesheet with filters
     */
    public function getTimesheet(array $filters): array
    {
        $query = TimeLog::with(['user', 'task']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['project_id'])) {
            $query->whereHas('task', function($q) use ($filters) {
                $q->where('project_id', $filters['project_id']);
            });
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Daily filter
        if (isset($filters['daily']) && $filters['daily']) {
            $date = $filters['date'] ?? Carbon::today();
            $query->whereDate('start_time', $date);
        }

        // Weekly filter
        if (isset($filters['weekly']) && $filters['weekly']) {
            $weekStart = $filters['week_start'] ?? Carbon::now()->startOfWeek();
            $weekEnd = $filters['week_end'] ?? Carbon::now()->endOfWeek();
            $query->whereBetween('start_time', [$weekStart, $weekEnd]);
        }

        $timeLogs = $query->orderBy('start_time', 'desc')->get();

        $totalMinutes = $timeLogs->sum(function ($log) {
            return $log->duration_minutes;
        });

        return [
            'time_logs' => $timeLogs,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
            'total_logs' => $timeLogs->count(),
        ];
    }

    /**
     * Get timeline activity feed
     */
        public function getTimeline(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        try {
            $query = TimeLog::with(['user', 'task.project']);

            // Apply filters
            if (isset($filters['user_id']) && !empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['project_id']) && !empty($filters['project_id'])) {
                $query->whereHas('task', function($q) use ($filters) {
                    $q->where('project_id', $filters['project_id']);
                });
            }

            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('start_time', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('start_time', '<=', $filters['end_date']);
            }

            // Filter by time log status (approval status)
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Filter by task status
            if (isset($filters['task_status']) && !empty($filters['task_status'])) {
                $query->whereHas('task', function($q) use ($filters) {
                    $q->where('status', $filters['task_status']);
                });
            }

            // Page-based pagination
            $paginator = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $timeLogs = $paginator->items();
            $activities = [];

            foreach ($timeLogs as $log) {
                try {
                    // Determine activity type
                    $activityType = $this->determineActivityType($log);

                    $activities[] = [
                        'id' => $log->id,
                        'type' => $activityType,
                        'user' => [
                            'id' => $log->user->id ?? null,
                            'name' => $log->user->name ?? 'Unknown',
                            'email' => $log->user->email ?? null,
                        ],
                        'task' => [
                            'id' => $log->task->id ?? null,
                            'title' => $log->task->title ?? 'Unknown Task',
                            'status' => $log->task->status ?? 'todo',
                        ],
                        'project' => [
                            'id' => $log->task->project->id ?? null,
                            'name' => $log->task->project->name ?? 'Unknown Project',
                        ],
                        'duration_minutes' => $this->calculateCurrentDuration($log),
                        'status' => $log->status ?? 'pending',
                        'note' => $log->note,
                        'start_time' => $log->start_time?->toIso8601String(),
                        'end_time' => $log->end_time?->toIso8601String(),
                        'is_paused' => !is_null($log->paused_at),
                        'timestamp' => $log->created_at->toIso8601String(),
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing timeline log: ' . $e->getMessage(), [
                        'log_id' => $log->id ?? 'unknown',
                    ]);
                    continue;
                }
            }

            // Fallback: jika belum ada log sama sekali dan halaman 1
            if (empty($activities) && $page === 1) {
                $taskQuery = Task::with(['project', 'assignee', 'timeLogs']);

                if (!empty($filters['user_id'])) {
                    $taskQuery->where('assigned_to', $filters['user_id']);
                }

                if (!empty($filters['project_id'])) {
                    $taskQuery->where('project_id', $filters['project_id']);
                }

                if (!empty($filters['task_status'])) {
                    $taskQuery->where('status', $filters['task_status']);
                }

                if (!empty($filters['start_date'])) {
                    $taskQuery->whereDate('created_at', '>=', $filters['start_date']);
                }

                if (!empty($filters['end_date'])) {
                    $taskQuery->whereDate('created_at', '<=', $filters['end_date']);
                }

                $tasks = $taskQuery->orderBy('created_at', 'desc')
                    ->limit($perPage)
                    ->get();

                foreach ($tasks as $task) {
                    $activities[] = [
                        'id' => 'task-' . $task->id,
                        'type' => 'task_created',
                        'user' => [
                            'id' => $task->assignee->id ?? null,
                            'name' => $task->assignee->name ?? 'Belum ditugaskan',
                            'email' => $task->assignee->email ?? null,
                        ],
                        'task' => [
                            'id' => $task->id,
                            'title' => $task->title ?? 'Tugas Baru',
                            'status' => $task->status ?? 'todo',
                        ],
                        'project' => [
                            'id' => $task->project->id ?? null,
                            'name' => $task->project->name ?? 'Unknown Project',
                        ],
                        'duration_minutes' => max(0, $task->timeLogs?->sum(function ($log) {
                            if (!$log->end_time) {
                                $startTime = \Carbon\Carbon::parse($log->start_time);
                                $now = \Carbon\Carbon::now();
                                
                                if ($log->paused_at) {
                                    $endTime = \Carbon\Carbon::parse($log->paused_at);
                                } else {
                                    $endTime = $now;
                                }
                                
                                $totalSeconds = $endTime->diffInSeconds($startTime);
                                $pausedSeconds = $log->paused_duration_minutes * 60;
                                
                                $netSeconds = max(0, $totalSeconds - $pausedSeconds);
                                return (int) ceil($netSeconds / 60);
                            }
                            return $log->duration_minutes ?? 0;
                        }) ?? 0),
                        'status' => 'pending',
                        'note' => 'Belum ada log waktu.',
                        'start_time' => $task->created_at?->toIso8601String(),
                        'end_time' => null,
                        'is_paused' => false,
                        'timestamp' => $task->created_at?->toIso8601String(),
                        'is_placeholder' => true,
                        'running_start_time' => $task->timeLogs?->whereNull('end_time')->sortByDesc('start_time')->first()?->start_time?->toIso8601String(),
                    ];
                }
                
                // For fallback, we don't really have "total" in the same sense, 
                // but we can pretend it's just one page.
                return [
                    'activities' => $activities,
                    'total' => count($activities),
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                ];
            }

            return [
                'activities' => $activities,
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ];
        } catch (\Exception $e) {
            Log::error('Timeline service error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);
            throw $e;
        }
    }

    /**
     * Determine activity type based on time log state
     */
    private function determineActivityType(TimeLog $log): string
    {
        // Check status changes first (approved/rejected)
        // Note: We'll track status changes separately if needed
        // For now, we focus on timer actions

        // If source is explicitly manual
        if ($log->source === 'manual') {
            return 'manual_entry';
        }

        // If source is timer, use timer types
        if ($log->source === 'timer') {
            if (!$log->end_time) {
                return $log->paused_at ? 'timer_paused' : 'timer_started';
            }
            return 'timer_stopped';
        }

        // Fallback for legacy logs (before source column)
        // If it's a manual entry (has end_time immediately after creation)
        if ($log->end_time && $log->created_at->diffInSeconds($log->end_time) < 300) {
            return 'manual_entry';
        }

        // If timer is still running
        if (!$log->end_time) {
            if ($log->paused_at) {
                return 'timer_paused';
            }
            return 'timer_started';
        }

        // Timer was stopped
        return 'timer_stopped';
    }

    /**
     * Approve a time log
     */
    public function approveTimeLog(string $timeLogId, string $approvedBy): TimeLog
    {
        $timeLog = TimeLog::findOrFail($timeLogId);

        if ($timeLog->status === 'approved') {
            throw new \Exception('Time log is already approved');
        }

        $timeLog->update([
            'status' => 'approved',
        ]);

        // Update task's actual_time_minutes
        $this->updateTaskActualTime($timeLog->task_id);

        return $timeLog->fresh();
    }

    /**
     * Reject a time log
     */
    public function rejectTimeLog(string $timeLogId, string $rejectionReason): TimeLog
    {
        $timeLog = TimeLog::findOrFail($timeLogId);

        if ($timeLog->status === 'rejected') {
            throw new \Exception('Time log is already rejected');
        }

        $timeLog->update([
            'status' => 'rejected',
            'note' => ($timeLog->note ? $timeLog->note . "\n\n" : '') . 'Rejection reason: ' . $rejectionReason,
        ]);

        return $timeLog->fresh();
    }

    /**
     * Update task's actual_time_minutes from approved time logs
     */
    public function updateTaskActualTime(string $taskId): void
    {
        $totalMinutes = TimeLog::where('task_id', $taskId)
            ->where('status', 'approved')
            ->sum(function ($log) {
                return $log->duration_minutes;
            });

        Task::where('id', $taskId)->update([
            'actual_time_minutes' => $totalMinutes,
        ]);
    }

    /**
     * Check for long-running timers and dispatch events
     */
    public function checkLongRunningTimers(int $maxHours = 8): array
    {
        $cutoffTime = Carbon::now()->subHours($maxHours);

        $longRunningTimers = TimeLog::whereNull('end_time')
            ->where('start_time', '<', $cutoffTime)
            ->with(['user', 'task'])
            ->get();

        $reminders = [];
        foreach ($longRunningTimers as $timer) {
            $hours = Carbon::parse($timer->start_time)->diffInHours(Carbon::now());

            // Dispatch event for notification
            event(new LongRunningTimerEvent($timer, $hours));

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

        return $reminders;
    }

    /**
     * Get timer for current user (with authorization check)
     */
    /**
     * Calculate current duration for a time log (including running/paused state)
     */
    private function calculateCurrentDuration(TimeLog $log): int
    {
        if ($log->end_time) {
            return max(0, $log->duration_minutes ?? 0);
        }

        // Timer is running or paused
        $startTime = Carbon::parse($log->start_time);
        $now = Carbon::now();
        
        // If paused, use paused_at as the end point for calculation
        if ($log->paused_at) {
            $endTime = Carbon::parse($log->paused_at);
        } else {
            $endTime = $now;
        }
        
        $totalSeconds = $endTime->diffInSeconds($startTime);
        $pausedSeconds = ($log->paused_duration_minutes ?? 0) * 60;
        
        $netSeconds = max(0, $totalSeconds - $pausedSeconds);
        return (int) ceil($netSeconds / 60);
    }

    private function getTimerForUser(string $timerId): TimeLog
    {
        $timer = TimeLog::where('id', $timerId)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->firstOrFail();

        return $timer;
    }
}
