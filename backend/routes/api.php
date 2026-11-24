<?php

use App\Modules\Auth\Http\Controllers\AddressController;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Catalog\Http\Controllers\PublicCatalogController;
use App\Modules\Catalog\Http\Controllers\SellerProductController;
use App\Modules\Inventory\Http\Controllers\SellerInventoryController;
use App\Modules\Order\Http\Controllers\CartController;
use App\Modules\Order\Http\Controllers\CheckoutController;
use App\Modules\Order\Http\Controllers\OrderController;
use App\Modules\Payment\Http\Controllers\PaymentController;
use App\Modules\Payment\Http\Controllers\PaymentWebhookController;
use App\Modules\Seller\Http\Controllers\AdminSellerReviewController;
use App\Modules\Seller\Http\Controllers\SellerApplicationController;
use App\Modules\Seller\Http\Controllers\SellerStoreController;
use App\Modules\Shipping\Http\Controllers\ShipmentController;
use App\Modules\Shipping\Http\Controllers\ShippingRateController;
use Illuminate\Support\Facades\Route;

// Health Check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'BeliIn API v1 is healthy',
        'data' => [
            'app' => 'BeliIn',
            'version' => '1.0.0',
            'status' => 'ok',
        ],
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Public Catalog Routes
Route::get('/categories', [PublicCatalogController::class, 'categories']);
Route::get('/categories/{slug}', [PublicCatalogController::class, 'categoryBySlug']);
Route::get('/products', [PublicCatalogController::class, 'products']);
Route::get('/products/{slug}', [PublicCatalogController::class, 'productBySlug']);
Route::get('/stores/{slug}', [PublicCatalogController::class, 'storeBySlug']);
Route::get('/stores/{slug}/products', [PublicCatalogController::class, 'storeProducts']);

// Public Shipping & Tracking Routes
Route::get('/shipping/rates', [ShippingRateController::class, 'calculate']);
Route::get('/shipments/track/{trackingNumber}', [ShipmentController::class, 'track']);
Route::post('/shipments/{trackingNumber}/events', [ShipmentController::class, 'addEvent']);
Route::post('/shipments/track/{trackingNumber}/events', [ShipmentController::class, 'addEvent']);

// Authenticated Routes (Buyer & User)
Route::middleware('auth:sanctum')->group(function () {
    // User Addresses
    Route::apiResource('addresses', AddressController::class);
    Route::patch('/addresses/{id}/default', [AddressController::class, 'setDefault']);

    // Cart Management
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);

    // Checkout
    Route::post('/checkout', [CheckoutController::class, 'checkout']);

    // Buyer Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Payments
    Route::post('/payments/{orderId}/initiate', [PaymentController::class, 'initiate']);
    Route::get('/payments/{idOrRef}', [PaymentController::class, 'show']);
    Route::post('/payments/{idOrRef}/simulate-pay', [PaymentController::class, 'simulatePay']);
});

// Payment Webhook (Public with signature check)
Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handle']);

// Seller Onboarding & Store Management
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/seller/applications', [SellerApplicationController::class, 'apply']);
    Route::get('/seller/application', [SellerApplicationController::class, 'status']);
    Route::get('/seller/store', [SellerStoreController::class, 'show']);
    Route::patch('/seller/store', [SellerStoreController::class, 'update']);

    // Seller Catalog Management
    Route::apiResource('/seller/products', SellerProductController::class);

    // Seller Inventory Management
    Route::get('/seller/inventory', [SellerInventoryController::class, 'index']);
    Route::post('/seller/inventory/adjust', [SellerInventoryController::class, 'adjust']);

    // Seller Shipment Creation
    Route::post('/seller/orders/{sellerOrderId}/ship', [ShipmentController::class, 'createForSellerOrder']);

    // Seller Orders Management
    Route::get('/seller/orders', [OrderController::class, 'sellerOrders']);
    Route::put('/seller/orders/{id}/status', [OrderController::class, 'updateSellerOrderStatus']);
});

// Admin Review Routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/seller-applications', [AdminSellerReviewController::class, 'index']);
    Route::post('/seller-applications/{id}/approve', [AdminSellerReviewController::class, 'approve']);
    Route::post('/seller-applications/{id}/reject', [AdminSellerReviewController::class, 'reject']);
});
