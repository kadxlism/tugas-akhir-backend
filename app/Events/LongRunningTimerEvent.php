<?php

namespace App\Events;

use App\Models\TimeLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LongRunningTimerEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TimeLog $timeLog;
    public int $hoursRunning;

    /**
     * Create a new event instance.
     */
    public function __construct(TimeLog $timeLog, int $hoursRunning)
    {
        $this->timeLog = $timeLog;
        $this->hoursRunning = $hoursRunning;
    }
}
