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
            // --- 1. Ambil Data Dasar dari User & Product Service ---
            $userRes = Http::get(env('USER_SERVICE_URL') . "/api/users/" . $request->user_id);
            $productRes = Http::get(env('PRODUCT_SERVICE_URL') . "/api/products/" . $request->product_id);

            // --- 2. Cek User ---
            if ($userRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'User tidak ditemukan di User-Service (Port 8000)!',
                ], 404);
            }

            // --- 3. Cek Produk (Ganti $prodRes jadi $productRes) ---
            if ($productRes->failed()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Produk tidak ditemukan di Product-Service (Port 8001)!',
                ], 404);
            }

            $userData = $userRes->json();
            $productData = $productRes->json();

            // --- 4. Simpan Order DULU ke Database (Biar dapet ID) ---
            $order = Order::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'product_name' => $productData['name'] ?? 'Barang Tanpa Nama',
                'quantity' => $request->quantity,
                'total_price' => ($productData['price'] ?? 0) * $request->quantity,
            ]);

            // --- 5. Baru Panggil Payment Service (Setelah ada $order->id) ---
            // Pakai URL dari .env biar konsisten
            $paymentRes = Http::post(env('PAYMENT_SERVICE_URL') . "/api/payments", [
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
