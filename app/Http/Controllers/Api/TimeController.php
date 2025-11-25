<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartTimerRequest;
use App\Http\Requests\PauseTimerRequest;
use App\Http\Requests\ResumeTimerRequest;
use App\Http\Requests\StopTimerRequest;
use App\Http\Requests\ManualTimeEntryRequest;
use App\Http\Requests\ApproveTimeLogRequest;
use App\Http\Requests\RejectTimeLogRequest;
use App\Http\Resources\TimeLogResource;
use App\Services\TimeLogService;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TimeController extends Controller
{
    public function __construct(
        protected TimeLogService $timeLogService
    ) {}

    /**
     * Start timer
     * POST /api/time/start
     */
    public function start(StartTimerRequest $request): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->startTimer(
                $request->validated()['task_id'],
                $request->validated()['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Timer started successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pause timer
     * POST /api/time/pause
     */
    public function pause(PauseTimerRequest $request): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->pauseTimer($request->validated()['timer_id']);

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Timer paused successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resume timer
     * POST /api/time/resume
     */
    public function resume(ResumeTimerRequest $request): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->resumeTimer($request->validated()['timer_id']);

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Timer resumed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop timer
     * POST /api/time/stop
     */
    public function stop(StopTimerRequest $request): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->stopTimer($request->validated()['timer_id']);

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Timer stopped successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create manual time entry
     * POST /api/time/manual
     */
    public function manual(ManualTimeEntryRequest $request): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->createManualEntry($request->validated());

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Time entry created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get time logs for a task
     * GET /api/time/task/{task_id}
     */
    public function getTaskTimeLogs(string $taskId): JsonResponse
    {
        try {
            $data = $this->timeLogService->getTimeLogsForTask($taskId);

            return response()->json([
                'success' => true,
                'data' => [
                    'time_logs' => TimeLogResource::collection($data['time_logs']),
                    'total_minutes' => $data['total_minutes'],
                    'total_hours' => $data['total_hours'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get timesheet
     * GET /api/timesheet
     */
    public function getTimesheet(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id',
            'project_id',
            'start_date',
            'end_date',
            'status',
            'daily',
            'weekly',
            'date',
            'week_start',
            'week_end',
        ]);

        try {
            $data = $this->timeLogService->getTimesheet($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'time_logs' => TimeLogResource::collection($data['time_logs']),
                    'total_minutes' => $data['total_minutes'],
                    'total_hours' => $data['total_hours'],
                    'total_logs' => $data['total_logs'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get timeline activity feed
     * GET /api/time/timeline
     */
    public function getTimeline(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'user_id',
                'project_id',
                'start_date',
                'end_date',
                'status', // Time log approval status: pending, approved, rejected
                'task_status', // Task status: todo, in_progress, review, done
            ]);

            $perPage = (int) $request->input('per_page', 20);
            $page = (int) $request->input('page', 1);

            $data = $this->timeLogService->getTimeline($filters, $perPage, $page);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Timeline error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 400);
        }
    }

    /**
     * Approve time log
     * POST /api/time/approve
     */
    public function approve(ApproveTimeLogRequest $request): JsonResponse
    {
        $request->validate([
            'time_log_id' => 'required|uuid|exists:time_logs,id',
        ]);

        Gate::authorize('approve', TimeLog::class);

        try {
            $timeLog = $this->timeLogService->approveTimeLog(
                $request->time_log_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Time log approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject time log
     * POST /api/time/reject
     */
    public function reject(RejectTimeLogRequest $request): JsonResponse
    {
        $request->validate([
            'time_log_id' => 'required|uuid|exists:time_logs,id',
        ]);

        Gate::authorize('approve', TimeLog::class);

        try {
            $timeLog = $this->timeLogService->rejectTimeLog(
                $request->time_log_id,
                $request->validated()['rejection_reason']
            );

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($timeLog->load(['task', 'user'])),
                'message' => 'Time log rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get active timer
     * GET /api/time/active
     */
    public function getActive(): JsonResponse
    {
        try {
            $activeTimer = $this->timeLogService->getActiveTimer();

            if (!$activeTimer) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active timer',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => new TimeLogResource($activeTimer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
