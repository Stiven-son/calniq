<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\GoogleCalendarOAuthController;
use App\Http\Controllers\ProjectExportController;

Route::get('/', function () {
    return view('landing');
});

Route::get('/widget-demo', function () {
    return view('widget-demo');
})->name('widget.demo')->middleware('signed');

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Google Calendar OAuth
    Route::get('/auth/google-calendar/redirect', [GoogleCalendarOAuthController::class, 'redirect'])
        ->name('google-calendar.redirect');
    Route::get('/auth/google-calendar/callback', [GoogleCalendarOAuthController::class, 'callback'])
        ->name('google-calendar.callback');
    Route::post('/auth/google-calendar/disconnect', [GoogleCalendarOAuthController::class, 'disconnect'])
        ->name('google-calendar.disconnect');

    // Project export
    Route::get('/export/{project}/bookings', [ProjectExportController::class, 'exportBookings'])
        ->name('project.export-bookings');
});