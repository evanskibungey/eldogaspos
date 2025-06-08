<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
    Route::post('/forgot-password', [App\Http\Controllers\API\AuthController::class, 'forgotPassword']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
        Route::get('/user', [App\Http\Controllers\API\AuthController::class, 'user']);
        
        // Customer endpoints
        Route::prefix('customers')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CustomerController::class, 'index']);
            Route::get('/search', [App\Http\Controllers\Api\CustomerController::class, 'search']);
            Route::get('/{id}', [App\Http\Controllers\Api\CustomerController::class, 'show']);
        });
        
        // More API endpoints will be added here in future phases
    });
    
    // Offline POS Sync routes (using web auth for now)
    Route::middleware('auth')->prefix('offline')->group(function () {
        Route::get('/products', [App\Http\Controllers\Api\OfflineSyncController::class, 'getProductsForOffline']);
        Route::post('/sync-sale', [App\Http\Controllers\Api\OfflineSyncController::class, 'syncOfflineSale']);
        Route::post('/batch-sync-sales', [App\Http\Controllers\Api\OfflineSyncController::class, 'batchSyncOfflineSales']);
        Route::get('/sync-status', [App\Http\Controllers\Api\OfflineSyncController::class, 'getSyncStatus']);
        Route::get('/failed-syncs', [App\Http\Controllers\Api\OfflineSyncController::class, 'getFailedSyncs']);
        Route::post('/retry-sync', [App\Http\Controllers\Api\OfflineSyncController::class, 'retrySyncLog']);
    });
    
    // Customer API routes (using web auth for POS integration)
    Route::middleware('auth')->prefix('customers')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CustomerController::class, 'index']);
        Route::get('/search', [App\Http\Controllers\Api\CustomerController::class, 'search']);
        Route::get('/{id}', [App\Http\Controllers\Api\CustomerController::class, 'show']);
    });
    
    // Cylinder API routes for POS integration
    Route::middleware('auth')->prefix('cylinders')->group(function () {
        Route::get('/search-customers', [App\Http\Controllers\Pos\CylinderController::class, 'searchCustomers']);
    });
});