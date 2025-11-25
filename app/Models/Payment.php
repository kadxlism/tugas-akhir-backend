<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'paid_at',
        'amount',
        'method',         // transfer, qris, etc.
        'payment_status', // pending, confirmed
    ];

    protected $dates = ['paid_at'];

    // Relasi ke Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function users() {
    return $this->belongsToMany(User::class);
}
}
