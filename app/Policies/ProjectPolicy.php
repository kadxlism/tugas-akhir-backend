<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'team', 'client']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Admin and PM can view all projects
        if (in_array($user->role, ['admin', 'pm'])) {
            return true;
        }

        // Team members can view projects they're assigned to
        if ($user->role === 'team') {
            return $user->projects()->where('project_id', $project->id)->exists();
        }

        // Clients can only view their own projects
        if ($user->role === 'client') {
            return $project->client_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Admin can update all projects
        if ($user->role === 'admin') {
            return true;
        }

        // PM can update projects they manage
        if ($user->role === 'pm') {
            return $user->projects()->where('project_id', $project->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }
}
