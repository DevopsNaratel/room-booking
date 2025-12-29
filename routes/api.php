<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    // Public room endpoints
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);

    // Protected endpoints (require auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/my-bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::put('/bookings/{booking}', [BookingController::class, 'update']);
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);

        // Admin-only API
        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::apiResource('rooms', AdminRoomController::class);
            Route::get('/bookings', [AdminBookingController::class, 'index']);
            Route::put('/bookings/{booking}/approve', [AdminBookingController::class, 'approve']);
            Route::put('/bookings/{booking}/reject', [AdminBookingController::class, 'reject']);
        });
    });
});
