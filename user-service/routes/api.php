<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::get('/test', function() {
    return response()->json(['message' => 'User Service is working']);
});

// PROVIDER routes (dikonsumsi service lain)
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// CONSUMER route (mengambil data dari order-service)
Route::get('/users/{id}/orders', [UserController::class, 'getUserOrders']);
