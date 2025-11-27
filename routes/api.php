<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\VariantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin and staff can manage categories, products, variants
    Route::middleware('role:admin,staff')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('variants', VariantController::class);
    });

    // Customers can view
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('variants', [VariantController::class, 'index']);

    // Orders
    Route::apiResource('orders', OrderController::class)->except(['update']);
    Route::patch('orders/{order}/status', [OrderController::class, 'update']);

    // Payments
    Route::post('orders/{order}/pay', [PaymentController::class, 'process']);
    Route::post('payments/webhook', [PaymentController::class, 'webhook'])->withoutMiddleware(['auth:sanctum']);

    // Recommendations
    Route::post('recommendations', [RecommendationController::class, 'recommendations']);
});
