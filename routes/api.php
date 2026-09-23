<?php

use App\Http\Controllers\Api\v1\AddressController;
use App\Http\Controllers\Api\v1\AdminController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CartController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CheckoutController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ReviewController;
use App\Http\Controllers\Api\v1\SellerController;
use App\Http\Controllers\Api\v1\StripeWebhookController;
use App\Http\Controllers\Api\v1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Authentication Endpoints
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('/user', [AuthController::class, 'user'])->name('api.v1.auth.user');
        });
    });

    // Catalog Endpoints
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('api.v1.categories.show');

    Route::get('/products', [ProductController::class, 'index'])->name('api.v1.products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.v1.products.show');

    // Authenticated Reviews
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('api.v1.reviews.store');

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('api.v1.wishlist.index');
        Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('api.v1.wishlist.toggle');

        // Addresses
        Route::get('/addresses', [AddressController::class, 'index'])->name('api.v1.addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('api.v1.addresses.store');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('api.v1.addresses.destroy');

        // Seller Portal
        Route::prefix('seller')->group(function (): void {
            Route::get('/dashboard', [SellerController::class, 'dashboard'])->name('api.v1.seller.dashboard');
            Route::get('/products', [SellerController::class, 'products'])->name('api.v1.seller.products.index');
            Route::post('/products', [SellerController::class, 'storeProduct'])->name('api.v1.seller.products.store');
            Route::get('/orders', [SellerController::class, 'orders'])->name('api.v1.seller.orders.index');
            Route::put('/orders/{orderItem}/fulfillment', [SellerController::class, 'updateFulfillment'])->name('api.v1.seller.orders.fulfillment');
            Route::post('/payout-setup', [SellerController::class, 'payoutSetup'])->name('api.v1.seller.payout');
        });

        // Admin Portal
        Route::prefix('admin')->group(function (): void {
            Route::get('/sellers', [AdminController::class, 'sellers'])->name('api.v1.admin.sellers.index');
            Route::put('/sellers/{seller}/status', [AdminController::class, 'updateSellerStatus'])->name('api.v1.admin.sellers.status');
            Route::get('/analytics', [AdminController::class, 'analytics'])->name('api.v1.admin.analytics');
        });
    });

    // Cart Endpoints
    Route::prefix('cart')->group(function (): void {
        Route::get('/', [CartController::class, 'index'])->name('api.v1.cart.index');
        Route::post('/items', [CartController::class, 'store'])->name('api.v1.cart.store');
        Route::put('/items/{productId}', [CartController::class, 'update'])->name('api.v1.cart.update');
        Route::delete('/items/{productId}', [CartController::class, 'destroy'])->name('api.v1.cart.destroy');
        Route::delete('/', [CartController::class, 'clear'])->name('api.v1.cart.clear');
    });

    // Checkout & Orders
    Route::prefix('checkout')->group(function (): void {
        Route::post('/session', [CheckoutController::class, 'createSession'])->name('api.v1.checkout.session');
        Route::get('/orders/{orderNumber}', [CheckoutController::class, 'getOrderStatus'])->name('api.v1.checkout.orders.status');
    });

    // Stripe Webhook Endpoint (Excluded from CSRF)
    Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('api.v1.webhooks.stripe');
});
