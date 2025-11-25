<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceController extends Controller
{
    public function index()
    {
        return Invoice::with(['project', 'issuer', 'items'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'issued_by' => 'required|exists:users,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'status' => 'required|in:draft,sent,paid,overdue',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.unit_price' => 'required|numeric',
        ]);

        $invoice = Invoice::create($data);

        foreach ($data['items'] as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return response()->json($invoice->load('items'));
    }

    public function show(Invoice $invoice)
    {
        return $invoice->load(['project', 'issuer', 'items', 'payments']);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $invoice->update($request->only([
            'status', 'due_date', 'tax', 'discount'
        ]));

        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->json(['message' => 'Deleted']);
    }
}