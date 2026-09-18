<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\AuthenticateWithTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (/api/v1)
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/health', HealthController::class);
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes with tenant context and device checking
Route::middleware(['auth:sanctum', AuthenticateWithTenant::class])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Devices management
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);
});
