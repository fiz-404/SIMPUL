<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AiExtractorController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected (butuh Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Pelanggan
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);

    // Pesanan
    Route::get('/pesanan', [OrderController::class, 'index']);
    Route::post('/pesanan', [OrderController::class, 'store']);
    Route::patch('/pesanan/{id}/status', [OrderController::class, 'updateStatus']);

    // AI Extraction
    Route::post('/extract-chat', [AiExtractorController::class, 'extract']);
});
