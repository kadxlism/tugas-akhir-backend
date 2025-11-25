<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeTracker extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'project_id',
        'start_time',
        'end_time',
        'duration',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_manual',
        'paused_at',
        'paused_duration',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'paused_at' => 'datetime',
        'is_manual' => 'boolean',
        'duration' => 'integer',
        'paused_duration' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getTotalDurationAttribute(): int
    {
        return $this->duration - $this->paused_duration;
    }
}
