<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Api\TaskController as ApiTaskController;
use App\Http\Controllers\Api\TimeTrackerController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\DashboardController;
use App\Exports\TasksExport;
use App\Imports\TasksImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Middleware\RequireAdmin;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== AUTH ==========
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// ========== ADMIN USERS ==========
Route::middleware(['auth:sanctum', RequireAdmin::class])->prefix('admin')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
});

// ========== PROJECTS ==========
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class);
    Route::get('projects/client/{clientId}', [\App\Http\Controllers\Api\ProjectController::class, 'getByClient']);
});

// ========== CLIENTS ==========
Route::middleware('auth:sanctum')->apiResource('clients', ClientController::class);

// ========== DASHBOARD ==========
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/statistics', [DashboardController::class, 'getStatistics']);
});

// ========== TASKS (RBAC) ==========
Route::middleware('auth:sanctum')->group(function () {
    // List tasks: admin sees all; others see tasks assigned to them
    Route::get('tasks', [ApiTaskController::class, 'listForCurrentUser']);

    // Create task: only admin or customer_service can create
    Route::post('tasks', [ApiTaskController::class, 'createByRole']);

    // Update task status: assignee can move to done; admin can update any
    Route::put('tasks/{task}', [ApiTaskController::class, 'updateByRole']);
    Route::patch('tasks/{task}/status', [ApiTaskController::class, 'updateStatus']);

    // Delete task: only admin or customer_service can delete
    Route::delete('tasks/{task}', [ApiTaskController::class, 'destroyByRole']);
});

// ========== TASKS EXPORT / IMPORT ==========
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/export', function () {
        return Excel::download(new TasksExport, 'tasks.xlsx');
    });

    Route::post('/tasks/import', function (Request $request) {
        $file = $request->file('file');
        Excel::import(new TasksImport, $file);
        return response()->json(['message' => 'Tasks imported successfully']);
    });

    Route::get('/export', function () {
        return Excel::download(new TasksExport, 'tasks.xlsx');
    });
});

// ========== TIME TRACKER ==========
use App\Http\Controllers\Api\TimeController;

Route::middleware('auth:sanctum')->prefix('time')->group(function () {
    Route::post('start', [TimeController::class, 'start']);
    Route::post('pause', [TimeController::class, 'pause']);
    Route::post('resume', [TimeController::class, 'resume']);
    Route::post('stop', [TimeController::class, 'stop']);
    Route::post('manual', [TimeController::class, 'manual']);
    Route::get('task/{task_id}', [TimeController::class, 'getTaskTimeLogs']);
    Route::get('active', [TimeController::class, 'getActive']);
    Route::get('timeline', [TimeController::class, 'getTimeline']);
    Route::post('approve', [TimeController::class, 'approve']);
    Route::post('reject', [TimeController::class, 'reject']);
});

Route::middleware('auth:sanctum')->prefix('timesheet')->group(function () {
    Route::get('/', [TimeController::class, 'getTimesheet']);
});

// ========== USER PROFILE ==========
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::put('profile/name', [UserProfileController::class, 'updateName']);
    Route::put('profile/email', [UserProfileController::class, 'updateEmail']);
    Route::put('profile/password', [UserProfileController::class, 'updatePassword']);
    
    // Device Sessions
    Route::get('devices', [\App\Http\Controllers\Api\DeviceController::class, 'index']);
    Route::delete('devices/{id}', [\App\Http\Controllers\Api\DeviceController::class, 'destroy']);
});
