<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\UserResource;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    // PROVIDER: Ambil semua user
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Users endpoint work',
            'users' => UserResource::collection(User::all())
        ]);

    }

    // PROVIDER: Ambil user by ID (dikonsumsi service lain)
    public function show(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'user' => new UserResource($user)
        ]);
    }

    // PROVIDER: Tambah user baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string',
            'email' => 'required|email|string',
            'phone' => 'required|string|max:15',
            'address' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'User creation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create($validator->validated());
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => new UserResource($user)
        ], 201);
    }

    // PROVIDER: Update user
    public function update(Request $request, int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
        'name'  => 'sometimes|string',
        'email' => 'sometimes|email',
        'phone' => 'sometimes|string|max:15',
        'address' => 'sometimes|string',
        ]);  

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'User update failed',
                'errors' => $validator->errors()
            ], 422);
        }        
        $user->update($validator->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => new UserResource($user)
        ]);
    }

    // PROVIDER: Hapus user
    public function destroy(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // CONSUMER: Ambil riwayat order milik user dari order-service
    public function getUserOrders(int $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // HTTP request ke order-service (consumer)
        $orderServiceUrl = config('services.order_service.url');
        $response = Http::get("{$orderServiceUrl}/api/orders/user/{$userId}");
        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders from order-service'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User orders retrieved successfully',
            'user'   => new UserResource($user),
            'orders' => $response->json(),
        ]);
    }
}
