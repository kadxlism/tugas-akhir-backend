<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'uploaded_by',
        'filename',
        'url',
        'uploaded_at',
    ];

    protected $dates = ['uploaded_at'];

    // Relasi: Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi: User yang mengunggah
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
