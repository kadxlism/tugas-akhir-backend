<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'paid_at' => 'required|date',
            'amount' => 'required|numeric',
            'method' => 'required|string',
            'payment_status' => 'required|in:pending,confirmed',
        ]);

        $payment = Payment::create($data);
        return response()->json($payment);
    }

    public function confirm(Payment $payment)
    {
        $payment->update(['payment_status' => 'confirmed']);
        return response()->json($payment);
    }

    public function index()
    {
        return Payment::with('invoice')->get();
    }
}