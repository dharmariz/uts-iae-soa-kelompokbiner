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
            'status' => $request->status ?? 'pending'
        ]);

        return response()->json($payment, 201);
    }

    // ✅ TAMBAH DI SINI
    public function show($id)
    {
        return response()->json(Payment::findOrFail($id));
    }

    // ✅ TAMBAH DI SINI
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
}
