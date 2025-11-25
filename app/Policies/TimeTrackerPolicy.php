<?php

namespace App\Policies;

use App\Models\TimeTracker;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimeTrackerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view time logs
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TimeTracker $timeTracker): bool
    {
        // Users can view their own time logs, or if they're admin/PM
        return $user->id === $timeTracker->user_id ||
               $user->is_admin ||
               $this->isProjectManager($user, $timeTracker);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create time logs
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimeTracker $timeTracker): bool
    {
        // Users can only update their own pending time logs
        return $user->id === $timeTracker->user_id && $timeTracker->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimeTracker $timeTracker): bool
    {
        // Users can only delete their own pending time logs
        return $user->id === $timeTracker->user_id && $timeTracker->status === 'pending';
    }

    /**
     * Determine whether the user can approve/reject time logs.
     */
    public function approve(User $user, TimeTracker $timeTracker): bool
    {
        // Only admin or project managers can approve/reject
        if ($user->is_admin) {
            return true;
        }

        // Check if user is a project manager for the project
        return $this->isProjectManager($user, $timeTracker);
    }

    /**
     * Check if user is a project manager for the time tracker's project
     */
    private function isProjectManager(User $user, TimeTracker $timeTracker): bool
    {
        if (!$timeTracker->project) {
            return false;
        }

        // Check if user has PM role in the project
        // This assumes projects have a project_manager_id or similar
        // Adjust based on your actual project structure
        return $timeTracker->project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TimeTracker $timeTracker): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TimeTracker $timeTracker): bool
    {
        return false;
    }
}
