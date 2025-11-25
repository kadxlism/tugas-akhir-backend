<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeTracker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckLongRunningTimers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timers:check-long-running {--hours=8}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for long-running timers and send reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxHours = (int) $this->option('hours');
        $cutoffTime = Carbon::now()->subHours($maxHours);

        $longRunningTimers = TimeTracker::whereNull('end_time')
            ->where('start_time', '<', $cutoffTime)
            ->with(['user', 'task'])
            ->get();

        if ($longRunningTimers->isEmpty()) {
            $this->info('No long-running timers found.');
            return 0;
        }

        $this->warn("Found {$longRunningTimers->count()} long-running timer(s):");

        foreach ($longRunningTimers as $timer) {
            $hours = Carbon::parse($timer->start_time)->diffInHours(Carbon::now());

            $message = sprintf(
                "Timer ID: %d | User: %s | Task: %s | Running for: %d hours",
                $timer->id,
                $timer->user->name ?? 'N/A',
                $timer->task->title ?? 'N/A',
                $hours
            );

            $this->line($message);

            // Log for monitoring
            Log::warning('Long-running timer detected', [
                'timer_id' => $timer->id,
                'user_id' => $timer->user_id,
                'task_id' => $timer->task_id,
                'hours_running' => $hours,
                'start_time' => $timer->start_time,
            ]);

            // Here you can add email/notification sending logic
            // Example: Mail::to($timer->user)->send(new LongRunningTimerNotification($timer));
        }

        $this->info('Long-running timer check completed.');
        return 0;
    }
}
