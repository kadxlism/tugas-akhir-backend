<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use App\Models\TimeLog;
use App\Policies\TimeTrackerPolicy;
use App\Events\LongRunningTimerEvent;
use App\Listeners\SendLongRunningTimerNotification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        TimeLog::class => TimeTrackerPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            LongRunningTimerEvent::class,
            SendLongRunningTimerNotification::class
        );
    }
}
