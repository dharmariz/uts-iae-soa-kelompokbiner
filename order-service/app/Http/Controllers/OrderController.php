<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    // ==============================
    // 1. PROVIDER
    // Menampilkan semua data order
    // ==============================
    public function index()
    {
        return response()->json(Order::all(), 200);
    }

    // ==============================
    // 2. CONSUMER
    // Ambil data dari service lain
    // lalu simpan order
    // ==============================
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {

            // ==============================
            // REQUEST KE USER SERVICE
            // ==============================
            $userRes = Http::timeout(5)->get(
                "http://localhost:8000/api/users/" . $request->user_id
            );

            // ==============================
            // REQUEST KE PRODUCT SERVICE
            // ==============================
            $prodRes = Http::timeout(5)->get(
                "http://localhost:8001/api/products/" . $request->product_id
            );

            // ==============================
            // VALIDASI USER
            // ==============================
            if ($userRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'User tidak ditemukan di User-Service!',
                    'target_id' => $request->user_id
                ], 404);
            }

            // ==============================
            // VALIDASI PRODUCT
            // ==============================
            if ($prodRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Produk tidak ditemukan di Product-Service!',
                    'target_id' => $request->product_id
                ], 404);
            }

            // ==============================
            // AMBIL JSON RESPONSE
            // ==============================
            $userData = $userRes->json();
            $productData = $prodRes->json();

            // ==============================
            // AMBIL DATA USER
            // ==============================
            $customerName = $userData['data']['name'] ?? 'Unknown User';

            $customerEmail = $userData['data']['email']
                ?? 'guest@mail.com';

            // ==============================
            // AMBIL DATA PRODUCT
            // ==============================
            $productName = $productData['data']['name']
                ?? 'Unknown Product';

            $productPrice = $productData['data']['price']
                ?? 0;

            // ==============================
            // SIMPAN ORDER
            // ==============================
            $order = Order::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'product_name' => $productName,
                'quantity' => $request->quantity,
                'total_price' => $productPrice * $request->quantity,
            ]);

            // ==============================
            // REQUEST KE PAYMENT SERVICE
            // ==============================
            $paymentRes = Http::post(
                "http://localhost:8003/api/payments",
                [
                    'order_id' => $order->id,
                    'total_amount' => $order->total_price,
                    'user_email' => $customerEmail,
                ]
            );

            // ==============================
            // RESPONSE FINAL
            // ==============================
            return response()->json([
                'status' => 'Success',
                'message' => 'Order berhasil dibuat!',
                'info_lintas_service' => [
                    'customer' => $customerName,
                    'product' => $productName,
                    'payment_status' => $paymentRes->successful()
                        ? 'Invoice Sent'
                        : 'Payment Service Down'
                ],
                'data' => $order
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'Error',
                'message' => 'Koneksi antar service terputus!',
                'debug' => $e->getMessage()
            ], 500);

        }
    }
}
