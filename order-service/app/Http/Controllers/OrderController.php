<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Helper Guzzle di Laravel

class OrderController extends Controller
{
    // 1. PROVIDER: Menampilkan data order (untuk dikonsumsi layanan lain)
    public function index()
    {
        return response()->json(Order::all(), 200);
    }

    // 2. CONSUMER: Mengambil data user dari service teman & simpan order
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // --- 1. Ambil Data dari Service Lain ---
            $userRes = Http::timeout(5)->get("http://localhost:8000/api/users/" . $request->user_id);
            $prodRes = Http::timeout(5)->get("http://localhost:8001/api/products/" . $request->product_id);

            // --- 2. Cek User (Pesan Error Spesifik) ---
            if ($userRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'User tidak ditemukan di User-Service (Port 8000)!',
                    'target_id' => $request->user_id
                ], 404);
            }

            // --- 3. Cek Produk (Pesan Error Spesifik) ---
            if ($prodRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Produk tidak ditemukan di Product-Service (Port 8001)!',
                    'target_id' => $request->product_id
                ], 404);
            }

            $userData = $userRes->json();
            $productData = $prodRes->json();

            // --- 4. Simpan Order di Database ---
            $order = Order::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'product_name' => $productData['name'] ?? 'Barang Tanpa Nama',
                'quantity' => $request->quantity,
                'total_price' => ($productData['price'] ?? 0) * $request->quantity,
            ]);

            // --- 5. Interaksi ke Payment Service ---
            $paymentRes = Http::post("http://localhost:8003/api/payments", [
                'order_id' => $order->id,
                'total_amount' => $order->total_price,
                'user_email' => $userData['email'] ?? 'guest@mail.com',
            ]);

            // --- 6. Response Lengkap ---
            return response()->json([
                'status' => 'Success',
                'message' => 'Order berhasil dibuat!',
                'info_lintas_service' => [
                    'customer' => $userData['name'] ?? 'Unknown User',
                    'product' => $productData['name'] ?? 'Unknown Product',
                    'payment_status' => $paymentRes->successful() ? 'Invoice Sent' : 'Payment Service Down'
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
