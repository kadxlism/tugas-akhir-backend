<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'issued_by',
        'issue_date',
        'due_date',
        'total_amount',
        'tax',
        'discount',
        'status', // draft, sent, paid, overdue
    ];

    // Relasi: Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi: Pembuat Invoice (User)
    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Relasi: Items
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Relasi: Payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Total dengan pajak dan diskon (optional helper)
    public function getFinalAmountAttribute()
    {
        $base = $this->total_amount;
        $tax = $this->tax ?? 0;
        $discount = $this->discount ?? 0;

        return ($base + $tax) - $discount;
    }
}
