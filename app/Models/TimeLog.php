<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasUuids;

    protected $table = 'time_logs';

    protected $fillable = [
        'id',
        'task_id',
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'note',
        'status',
        'paused_at',
        'paused_duration_minutes',
        'source',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'duration_minutes' => 'integer',
        'paused_duration_minutes' => 'integer',
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

    // Helper methods
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getDurationInHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }
}
