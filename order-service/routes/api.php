<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

// Endpoint untuk Provider
Route::get('/orders', [OrderController::class, 'index']);
// Endpoint untuk Consumer
Route::post('/orders', [OrderController::class, 'store']);
