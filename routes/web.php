<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\CylinderController as AdminCylinderController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Pos\InventoryController;
use App\Http\Controllers\Pos\CreditController;

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Modified dashboard route to handle role-based redirection
Route::get('/dashboard', function () {
    return auth()->user()->isAdmin() 
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pos.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Direct link to POS system for admins
        Route::get('/pos-access', function() {
            return redirect()->route('pos.sales.create');
        })->name('pos.access');
        
        // Users Management
        Route::resource('users', UserController::class);
        
        // Inventory Management
        Route::resource('categories', CategoryController::class);
        
        // Products with Stock Management
        Route::resource('products', ProductController::class);
        Route::patch('/products/{product}/update-stock', [ProductController::class, 'updateStock'])
            ->name('products.update-stock');
        Route::get('/products/{product}/movements', [ProductController::class, 'movements'])
            ->name('products.movements');
            
        // Stock Movements
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
            Route::get('/movements/export', [StockMovementController::class, 'export'])->name('movements.export');
            Route::get('/low-stock', [StockMovementController::class, 'lowStock'])->name('low-stock');
            Route::post('/adjust-stock', [StockMovementController::class, 'adjustStock'])->name('stock.adjust');
        });
        
        // Credit Management (for admin)
        Route::prefix('credits')->name('credits.')->group(function () {
            Route::get('/', [CreditController::class, 'index'])->name('index');
            Route::get('/{customer}', [CreditController::class, 'show'])->name('show');
            Route::get('/{customer}/payment', [CreditController::class, 'recordPaymentForm'])->name('payment.form');
            Route::post('/{customer}/payment', [CreditController::class, 'recordPayment'])->name('payment.store');
        });
        
        // Cylinder Management
        Route::prefix('cylinders')->name('cylinders.')->group(function () {
            Route::get('/', [AdminCylinderController::class, 'index'])->name('index');
            Route::get('/create', [AdminCylinderController::class, 'create'])->name('create');
            Route::post('/', [AdminCylinderController::class, 'store'])->name('store');
            Route::get('/{cylinder}', [AdminCylinderController::class, 'show'])->name('show');
            Route::get('/{cylinder}/edit', [AdminCylinderController::class, 'edit'])->name('edit');
            Route::put('/{cylinder}', [AdminCylinderController::class, 'update'])->name('update');
            Route::post('/{cylinder}/complete', [AdminCylinderController::class, 'complete'])->name('complete');
            Route::post('/{cylinder}/cancel', [AdminCylinderController::class, 'cancel'])->name('cancel');
            Route::delete('/{cylinder}', [AdminCylinderController::class, 'destroy'])->name('destroy');
            Route::get('/search-customers', [AdminCylinderController::class, 'searchCustomers'])->name('search-customers');
        });
        
        // Customer Management
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\CustomerController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('store');
            Route::post('/quick-create', [\App\Http\Controllers\Admin\CustomerController::class, 'quickStore'])->name('quick-store');
            Route::get('/search', [\App\Http\Controllers\Admin\CustomerController::class, 'searchCustomers'])->name('search');
            Route::get('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('show');
            Route::get('/{customer}/edit', [\App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('update');
            Route::delete('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('destroy');
            Route::post('/{customer}/adjust-balance', [\App\Http\Controllers\Admin\CustomerController::class, 'adjustBalance'])->name('adjust-balance');
        });
        
        // Reports - Enhanced Reporting System
        Route::prefix('reports')->name('reports.')->group(function () {
            // Main Report Views
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/users', [ReportController::class, 'users'])->name('users');
            Route::get('/stock-movements', [ReportController::class, 'stockMovements'])->name('stock-movements');
            
            // Report Exports
            Route::get('/sales/export', [ReportController::class, 'exportSales'])->name('sales.export');
            Route::get('/sales/export-detailed', [ReportController::class, 'exportDetailedSales'])->name('sales.export-detailed');
            Route::get('/inventory/export', [ReportController::class, 'exportInventory'])->name('inventory.export');
            Route::get('/users/export', [ReportController::class, 'exportUsers'])->name('users.export');
            Route::get('/stock-movements/export', [ReportController::class, 'exportStockMovements'])
                ->name('stock-movements.export');
            
            // Debug endpoint
            Route::get('/debug-chart', [ReportController::class, 'debugChart'])->name('debug-chart');
        });
        
        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // Shared POS Routes (accessible by both cashiers and admins)
    Route::middleware(['admin.or.cashier'])->prefix('pos')->name('pos.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [PosController::class, 'index'])->name('dashboard');
        
        // Inventory Management Routes
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/search', [InventoryController::class, 'search'])->name('search');
            Route::get('/product/{id}', [InventoryController::class, 'product'])->name('product');
            Route::get('/product/{id}/update-stock', [InventoryController::class, 'updateStockForm'])->name('update-stock-form');
            Route::post('/product/{id}/update-stock', [InventoryController::class, 'updateStock'])->name('update-stock');
        });
        
        // Sales
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
        Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
        
        // Credit Management
        Route::prefix('credits')->name('credits.')->group(function () {
            Route::get('/', [CreditController::class, 'index'])->name('index');
            Route::get('/{customer}', [CreditController::class, 'show'])->name('show');
            Route::get('/{customer}/payment', [CreditController::class, 'recordPaymentForm'])->name('payment.form');
            Route::post('/{customer}/payment', [CreditController::class, 'recordPayment'])->name('payment.store');
        });
        
        // Cylinder Management - Redirect to Admin Controller
        Route::prefix('cylinders')->name('cylinders.')->group(function () {
            Route::get('/', [AdminCylinderController::class, 'index'])->name('index');
            Route::get('/create', [AdminCylinderController::class, 'create'])->name('create');
            Route::post('/', [AdminCylinderController::class, 'store'])->name('store');
            Route::get('/{cylinder}', [AdminCylinderController::class, 'show'])->name('show');
            Route::post('/{cylinder}/complete', [AdminCylinderController::class, 'complete'])->name('complete');
            Route::post('/{cylinder}/quick-complete', [AdminCylinderController::class, 'quickComplete'])->name('quick-complete');
            Route::post('/{cylinder}/quick-return', [AdminCylinderController::class, 'quickReturn'])->name('quick-return');
            Route::get('/search-customers', [AdminCylinderController::class, 'searchCustomers'])->name('search-customers');
        });
        
        // Customer API endpoints for POS
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::post('/quick-create', [\App\Http\Controllers\Admin\CustomerController::class, 'quickStore'])->name('quick-store');
            Route::get('/search', [\App\Http\Controllers\Admin\CustomerController::class, 'searchCustomers'])->name('search');
        });
        
        // Debug route
        Route::post('/debug-sale', [PosController::class, 'debugSale'])->name('debug-sale');
        
        // Error testing page (remove in production)
        Route::get('/test-errors', function() {
            return view('test-pos-errors');
        })->name('test-errors');
    });
});

require __DIR__.'/auth.php';