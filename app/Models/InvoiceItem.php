<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    // Relasi ke Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Hitung total harga otomatis (optional)
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}
