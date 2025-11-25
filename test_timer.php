<?php

use App\Models\Task;
use App\Services\TimeLogService;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock Auth
$user = \App\Models\User::first();
Auth::login($user);

echo "User: " . $user->name . "\n";

$service = app(TimeLogService::class);

// Create a dummy task
$task = Task::create([
    'title' => 'Test Timer Task ' . time(),
    'project_id' => 1, // Assuming project 1 exists
    'status' => 'in_progress',
    'priority' => 'medium',
]);

echo "Task created: " . $task->id . "\n";

// Start timer
echo "Starting timer...\n";
$timer = $service->startTimer($task->id);
echo "Timer started: " . $timer->id . " at " . $timer->start_time . "\n";

// Wait 2 seconds
sleep(2);

// Stop timer
echo "Stopping timer...\n";
$stoppedTimer = $service->stopTimer($timer->id);
echo "Timer stopped at " . $stoppedTimer->end_time . "\n";
echo "Duration minutes: " . $stoppedTimer->duration_minutes . "\n";

if ($stoppedTimer->duration_minutes < 1) {
    echo "FAIL: Duration should be at least 1 minute.\n";
} else {
    echo "PASS: Duration is " . $stoppedTimer->duration_minutes . " minute(s).\n";
}

// Cleanup
$task->delete();
