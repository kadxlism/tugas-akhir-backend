<?php

require_once 'vendor/autoload.php';

use App\Models\Task;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating new task with paket:\n";
echo "=============================\n";

$taskData = [
    'project_id' => 1,
    'title' => 'Test Task Baru',
    'paket' => 'startup',
    'description' => 'Test deskripsi',
    'status' => 'todo',
    'assigned_to' => 4,
    'due_date' => '2025-12-31',
    'priority' => 'medium'
];

echo "Data yang akan disimpan:\n";
print_r($taskData);
echo "\n";

$task = Task::create($taskData);

echo "Task berhasil dibuat!\n";
echo "Task ID: {$task->id}\n";
echo "Title: {$task->title}\n";
echo "Paket: " . ($task->paket ?? 'NULL') . "\n";
echo "Status: {$task->status}\n";
echo "Assigned to: " . ($task->assigned_to ?? 'NULL') . "\n";

// Reload from database
$taskReloaded = Task::find($task->id);
echo "\nSetelah reload dari database:\n";
echo "Paket: " . ($taskReloaded->paket ?? 'NULL') . "\n";

// Get as JSON (like API response)
echo "\nJSON Response (seperti API):\n";
echo json_encode($taskReloaded, JSON_PRETTY_PRINT);

