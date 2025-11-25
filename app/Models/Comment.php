<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'created_at',
    ];

    public $timestamps = false; // karena kita mengatur created_at manual (optional)

    protected $dates = ['created_at'];

    // Relasi ke Task
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
