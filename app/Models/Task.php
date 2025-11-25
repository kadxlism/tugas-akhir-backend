<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',   // todo, in_progress, review, done
        'due_date',
        'priority', // low, medium, high
        'paket',    // startup, business, enterprise, elite, custom
        'category', // Company Profil, Web Design, SMM, Logo
        'actual_time_minutes',
    ];

    protected $dates = ['due_date'];

    // Relasi: Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi: User yang ditugaskan
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Relasi: Komentar
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Relasi: Time Logs
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }
}
