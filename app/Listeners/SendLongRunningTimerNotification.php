<?php

namespace App\Listeners;

use App\Events\LongRunningTimerEvent;
use App\Notifications\LongRunningTimerNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLongRunningTimerNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LongRunningTimerEvent $event): void
    {
        $event->timeLog->user->notify(
            new LongRunningTimerNotification($event->timeLog, $event->hoursRunning)
        );
    }
}
