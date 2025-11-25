<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'team', 'client']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // Admin and PM can view all tasks
        if (in_array($user->role, ['admin', 'pm'])) {
            return true;
        }

        // Team members can view tasks assigned to them or in their projects
        if ($user->role === 'team') {
            return $task->assigned_to === $user->id || 
                   $user->projects()->where('project_id', $task->project_id)->exists();
        }

        // Clients can view tasks in their projects
        if ($user->role === 'client') {
            return $task->project->client_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Admin can update all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // PM can update tasks in their projects
        if ($user->role === 'pm') {
            return $user->projects()->where('project_id', $task->project_id)->exists();
        }

        // Team members can update tasks assigned to them
        if ($user->role === 'team') {
            return $task->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return in_array($user->role, ['admin', 'pm']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->role === 'admin';
    }
}
