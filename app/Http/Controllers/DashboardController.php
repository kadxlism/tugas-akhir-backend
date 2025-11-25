<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStatistics(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'all'); // all, day, month, year
        
        // Set date range based on filter
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;
        
        switch ($filter) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
        }
        
        // Get total data counts (always fresh from database)
        $totalData = $this->getTotalData($startDate, $endDate);
        
        // Get task status counts (always fresh from database)
        $taskStatus = $this->getTaskStatus($startDate, $endDate, $user);
        
        // Get client payment status counts (always fresh from database)
        $clientStatus = $this->getClientStatus($startDate, $endDate);
        
        return response()->json([
            'total_data' => $totalData,
            'task_status' => $taskStatus,
            'client_status' => $clientStatus,
            'filter' => $filter
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }
    
    private function getTotalData($startDate, $endDate)
    {
        try {
            // Use fresh query without any caching
            $clientsQuery = Client::query()->withoutGlobalScopes();
            $projectsQuery = Project::query()->withoutGlobalScopes();
            $tasksQuery = Task::query()->withoutGlobalScopes();
            
            if ($startDate && $endDate) {
                $clientsQuery->whereBetween('created_at', [$startDate, $endDate]);
                $projectsQuery->whereBetween('created_at', [$startDate, $endDate]);
                $tasksQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            
            // Get fresh counts directly from database
            $clientsCount = $clientsQuery->count();
            $projectsCount = $projectsQuery->count();
            $tasksCount = $tasksQuery->count();
            
            return [
                'clients' => (int) $clientsCount,
                'projects' => (int) $projectsCount,
                'tasks' => (int) $tasksCount
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting total data: ' . $e->getMessage());
            return [
                'clients' => 0,
                'projects' => 0,
                'tasks' => 0
            ];
        }
    }
    
    private function getTaskStatus($startDate, $endDate, $user)
    {
        try {
            // Use fresh query without any caching
            // IMPORTANT: For task status, we want ALL tasks regardless of creation date
            // The filter should only affect total_data, not status counts
            $tasksQuery = Task::query()->withoutGlobalScopes();
            
            // If not admin or customer_service, only show tasks assigned to user
            if (!in_array($user->role, ['admin', 'customer_service'])) {
                $tasksQuery->where('assigned_to', $user->id);
            }
            
            // Don't filter by created_at for status calculation - we want all tasks
            // Status counts should show current state of all tasks, not just newly created ones
            
            // Get fresh data directly from database
            $tasks = $tasksQuery->get();
            
            $active = 0;
            $completed = 0;
            $overdue = 0;
            
            $today = Carbon::now()->startOfDay();
            
            \Log::info('Calculating task status for ' . $tasks->count() . ' tasks');
            
            foreach ($tasks as $task) {
                // Check if task is completed
                if ($task->status === 'done') {
                    $completed++;
                } 
                // Check if task is active (todo, in_progress, or review)
                elseif (in_array($task->status, ['todo', 'in_progress', 'review'])) {
                    // Check if task is overdue
                    if ($task->due_date) {
                        try {
                            $dueDate = Carbon::parse($task->due_date)->startOfDay();
                            if ($dueDate->lt($today)) {
                                $overdue++;
                            } else {
                                $active++;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Error parsing due_date for task ' . $task->id . ': ' . $e->getMessage());
                            // If can't parse due_date, consider as active
                            $active++;
                        }
                    } else {
                        // No due date means it's active
                        $active++;
                    }
                }
            }
            
            \Log::info('Task status result: active=' . $active . ', completed=' . $completed . ', overdue=' . $overdue);
            
            return [
                'active' => (int) $active,
                'completed' => (int) $completed,
                'overdue' => (int) $overdue
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting task status: ' . $e->getMessage());
            return [
                'active' => 0,
                'completed' => 0,
                'overdue' => 0
            ];
        }
    }
    
    private function getClientStatus($startDate, $endDate)
    {
        try {
            // Use fresh query without any caching
            // IMPORTANT: For client status, we want ALL clients regardless of creation date
            // The filter should only affect total_data, not status counts
            $clientsQuery = Client::query()->withoutGlobalScopes();
            
            // Don't filter by created_at for status calculation - we want all clients
            // Status counts should show current state of all clients, not just newly created ones
            
            // Get fresh data directly from database
            $clients = $clientsQuery->get();
            
            $paid = 0;
            $pending = 0;
            $overdue = 0;
            
            $today = Carbon::now()->startOfDay();
            
            \Log::info('Calculating client status for ' . $clients->count() . ' clients');
            
            foreach ($clients as $client) {
                try {
                    $dpValue = strtolower(trim($client->dp ?? ''));
                    $isPaid = in_array($dpValue, ['paid', 'lunas', 'completed']);
                    
                    if (!$client->deadline) {
                        // If no deadline, consider as pending
                        $pending++;
                        continue;
                    }
                    
                    $deadline = Carbon::parse($client->deadline)->startOfDay();
                    
                    if ($isPaid) {
                        $paid++;
                    } elseif ($deadline->gte($today)) {
                        $pending++;
                    } else {
                        $overdue++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error processing client ' . $client->id . ': ' . $e->getMessage());
                    $pending++; // Default to pending if there's an error
                }
            }
            
            \Log::info('Client status result: paid=' . $paid . ', pending=' . $pending . ', overdue=' . $overdue);
            
            return [
                'paid' => (int) $paid,
                'pending' => (int) $pending,
                'overdue' => (int) $overdue
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting client status: ' . $e->getMessage());
            return [
                'paid' => 0,
                'pending' => 0,
                'overdue' => 0
            ];
        }
    }
}

