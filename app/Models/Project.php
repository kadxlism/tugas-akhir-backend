<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_id', // User ID (for backward compatibility)
        'client_table_id', // Client table ID (from clients table)
        'status',
        'start_date',
        'end_date',
        'description',
    ];

    // Relasi: Client (User) - for backward compatibility
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Relasi: Client (from clients table)
    public function clientData()
    {
        return $this->belongsTo(Client::class, 'client_table_id');
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    // Relasi: Tasks
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Relasi: Files
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    // Relasi: Invoices
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
