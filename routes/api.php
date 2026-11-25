<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\MerchantProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\ComparisonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Stores
Route::apiResource('stores', StoreController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::post('stores/{store}/sync', [StoreController::class, 'syncProducts'])->name('stores.sync');
Route::post('stores/sync-all', [StoreController::class, 'syncAll'])->name('stores.sync-all');

// Products
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

// Store Products
Route::prefix('stores/{store}')->group(function () {
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::get('products/{product}', [ProductController::class, 'showInStore']);
});

// Categories
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::get('categories/{category}/products', [CategoryController::class, 'products']);
Route::post('categories/{category}/sync', [CategoryController::class, 'syncProducts'])->name('categories.sync');
Route::post('categories/sync-all', [CategoryController::class, 'syncAll'])->name('categories.sync-all');

// Merchants
Route::apiResource('merchants', MerchantController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::prefix('merchants/{merchant}')->group(function () {
    Route::apiResource('products', MerchantProductController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('orders', [MerchantController::class, 'orders']);
    Route::get('ledger', [MerchantController::class, 'ledger']);
    Route::get('settlements', [MerchantController::class, 'settlements']);
    Route::get('balance', [MerchantController::class, 'balance']);
});

// Orders
Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store', 'update']);
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

// Settlements
Route::apiResource('settlements', SettlementController::class)->only(['index', 'show']);
Route::post('settlements/{settlement}/confirm', [SettlementController::class, 'confirm']);
Route::patch('settlements/{settlement}/pay', [SettlementController::class, 'markAsPaid']);

// Comparison API
Route::get('comparison', [ComparisonController::class, 'compare']);
Route::get('stores', [StoreController::class, 'index']);
Route::get('stores/{store}/products/count', [StoreController::class, 'productsCount']);
