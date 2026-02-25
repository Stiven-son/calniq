<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;

// Public API for widget
Route::prefix('v1/{tenantSlug}')->group(function () {
    Route::get('/services', [PublicController::class, 'services']);
    Route::get('/availability', [PublicController::class, 'availability']);
    Route::post('/promo/validate', [PublicController::class, 'validatePromo']);
    Route::post('/bookings', [PublicController::class, 'createBooking']);
    Route::get('/booking/{reference}', [PublicController::class, 'getBooking']);
    Route::get('/pricing/{categorySlug}', [PublicController::class, 'pricing']);
});

// Auth API
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Admin API (protected, project-scoped)
Route::prefix('admin/{projectSlug}')->middleware('auth:sanctum')->group(function () {
    // Services
    Route::get('/services', [AdminController::class, 'listServices']);
    Route::post('/services', [AdminController::class, 'createService']);
    Route::put('/services/{id}', [AdminController::class, 'updateService']);
    Route::delete('/services/{id}', [AdminController::class, 'deleteService']);

    // Bookings
    Route::get('/bookings', [AdminController::class, 'listBookings']);
    Route::get('/bookings/{id}', [AdminController::class, 'getBooking']);
    Route::put('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
    Route::put('/bookings/{id}/reschedule', [AdminController::class, 'rescheduleBooking']);

    // Promo Codes
    Route::get('/promo-codes', [AdminController::class, 'listPromoCodes']);
    Route::post('/promo-codes', [AdminController::class, 'createPromoCode']);
    Route::put('/promo-codes/{id}', [AdminController::class, 'updatePromoCode']);
    Route::delete('/promo-codes/{id}', [AdminController::class, 'deletePromoCode']);

    // Webhooks
    Route::get('/webhooks', [AdminController::class, 'listWebhooks']);
    Route::post('/webhooks', [AdminController::class, 'createWebhook']);
    Route::put('/webhooks/{id}', [AdminController::class, 'updateWebhook']);
    Route::delete('/webhooks/{id}', [AdminController::class, 'deleteWebhook']);
    Route::post('/webhooks/{id}/test', [AdminController::class, 'testWebhook']);
});