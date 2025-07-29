<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\CreditController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\PosController;

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

// Public Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Dashboard Routes
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
    Route::get('/cashier/dashboard', [DashboardController::class, 'cashierDashboard']);
    
    // User Management Routes
    Route::apiResource('users', 'App\Http\Controllers\API\UserController');
    
    // Category Management Routes
    Route::apiResource('categories', CategoryController::class);
    
    // Product Management Routes
    Route::apiResource('products', ProductController::class);
    Route::get('/products/categories', [ProductController::class, 'categories']);
    Route::post('/products/{id}/stock', [ProductController::class, 'updateStock']);
    
    // Sales Routes
    Route::apiResource('sales', SaleController::class);
    Route::get('/pos/recent-sales', [SaleController::class, 'recentSales']);
    Route::post('/sales/{id}/void', [SaleController::class, 'void']);
    Route::post('/stock/check', [SaleController::class, 'checkStock']);
    
    // Enhanced POS Routes
    Route::prefix('pos')->group(function () {
        Route::get('/dashboard', [PosController::class, 'dashboard']);
        Route::get('/statistics', [PosController::class, 'getStatistics']);
        Route::get('/recent-sales', [PosController::class, 'recentSales']);
        Route::get('/products-by-category', [PosController::class, 'productsByCategory']);
        Route::post('/check-stock', [PosController::class, 'checkStock']);
    });
    
    // Inventory Routes
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/search', [InventoryController::class, 'search']);
        Route::get('/categories', [InventoryController::class, 'categories']);
        Route::get('/categories/{id}', [InventoryController::class, 'categoryDetails']);
        Route::get('/categories/{id}/products', [InventoryController::class, 'productsByCategory']);
        Route::get('/products/{id}', [InventoryController::class, 'product']);
        Route::post('/products/{id}/stock', [InventoryController::class, 'updateStock']);
        Route::get('/low-stock', [InventoryController::class, 'lowStockProducts']);
        Route::get('/out-of-stock', [InventoryController::class, 'outOfStockProducts']);
        Route::get('/stock-movements/{id}', [InventoryController::class, 'stockMovements']);
    });
    
    // Credit Management Routes
    Route::prefix('credits')->group(function () {
        Route::get('/', [CreditController::class, 'index']);
        Route::get('/{customer}', [CreditController::class, 'show']);
        Route::post('/{customer}/payment', [CreditController::class, 'recordPayment']);
    });
    
    // Reports Routes
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'salesReport']);
        Route::get('/sales/export', [ReportController::class, 'exportSalesReport']);
        Route::get('/inventory', [ReportController::class, 'inventoryReport']);
        Route::get('/inventory/export', [ReportController::class, 'exportInventoryReport']);
        Route::get('/customers', [ReportController::class, 'customersReport']);
        Route::get('/customers/export', [ReportController::class, 'exportCustomersReport']);
        Route::get('/cashier-performance', [ReportController::class, 'cashierPerformanceReport']);
        Route::get('/dashboard', [ReportController::class, 'dashboardReport']);
    });
    
    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/{key}', [SettingController::class, 'show']);
        Route::post('/multiple', [SettingController::class, 'getMultiple']);
    });     
    
});

// Fallback for undefined API routes
Route::fallback(function(){
    return response()->json([
        'message' => 'API endpoint not found. Please check the URL and method.'
    ], 404);
});