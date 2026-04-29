<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::all());
    }

    public function store(Request $request)
    {
        $payment = Payment::create([
            'product_id' => $request->product_id,
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

        return response()->json($payment, 201);
    }

    public function show($id)
    {
        return response()->json(Payment::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update($request->all());

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'message' => 'Data not found'
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

    // ✅ TAMBAHAN - CONSUMER ke OrderService
    public function processPayment(Request $request)
    {
        $orderResponse = Http::get('http://localhost:8000/api/orders/' . $request->order_id);

        if ($orderResponse->failed()) {
            return response()->json([
                'message' => 'Gagal mengambil data order dari OrderService'
            ], 502);
        }

        $order = $orderResponse->json();

        $payment = Payment::create([
            'order_id' => $request->order_id,
            'amount' => $order['amount'] ?? $request->amount,
            'status' => 'paid'
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil',
            'payment' => $payment,
            'order' => $order
        ], 201);
    }
}