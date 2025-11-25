<?php

namespace App\Notifications;

use App\Models\TimeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LongRunningTimerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public TimeLog $timeLog;
    public int $hoursRunning;

    /**
     * Create a new notification instance.
     */
    public function __construct(TimeLog $timeLog, int $hoursRunning)
    {
        $this->timeLog = $timeLog;
        $this->hoursRunning = $hoursRunning;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Long Running Timer Alert')
            ->line("Your timer for task '{$this->timeLog->task->title}' has been running for {$this->hoursRunning} hours.")
            ->line('Please remember to stop the timer when you finish working.')
            ->action('View Timer', url('/time-tracker'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'long_running_timer',
            'time_log_id' => $this->timeLog->id,
            'task_id' => $this->timeLog->task_id,
            'task_title' => $this->timeLog->task->title,
            'hours_running' => $this->hoursRunning,
            'message' => "Timer for '{$this->timeLog->task->title}' has been running for {$this->hoursRunning} hours.",
        ];
    }
}
