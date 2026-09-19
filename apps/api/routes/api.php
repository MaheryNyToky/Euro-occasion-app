<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\UnitController;
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

// Authenticated routes with tenant context and device tracking
Route::middleware(['auth:sanctum', AuthenticateWithTenant::class])->group(function () {
    // Auth & Identity
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Devices management
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);

    // Units and conversions
    Route::get('/units', [UnitController::class, 'index']);
    Route::post('/units', [UnitController::class, 'store']);
    Route::post('/units/conversions', [UnitController::class, 'storeConversion']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    // Products & Variants
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::post('/products/{id}/archive', [ProductController::class, 'archive']);

    // Transactional stock commands & queries
    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::post('/stock-movements', [StockMovementController::class, 'store']);
    Route::post('/stock-movements/{id}/reverse', [StockMovementController::class, 'reverse']);

    // Stock balances projection
    Route::get('/stock-balances', [StockBalanceController::class, 'index']);
    Route::get('/warehouses', [WarehouseController::class, 'index']);

    // Suppliers & Customers
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
});
