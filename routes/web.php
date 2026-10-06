<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\CylinderController as AdminCylinderController;
use App\Http\Controllers\Admin\RiderController;
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

/*
| Short link to the customer app, for use in SMS.
|
| The Play Store URL is 90 characters; this is about 25, which is the
| difference between one billed message and two on every thank-you sent. Public
| and unauthenticated on purpose - it is opened by customers, from a text.
|
| 302 rather than 301: the destination is a setting, and a permanently cached
| redirect could not be repointed at an iOS listing or a chooser page later.
*/
Route::get('/app', function () {
    $destination = trim((string) setting('app_store_url', ''));

    abort_if($destination === '', 404);

    // Both settings are edited by hand, and putting the short link into the
    // destination field too is an easy slip - it makes this route redirect to
    // itself, which the browser shows customers as ERR_TOO_MANY_REDIRECTS.
    // Fail visibly in the log instead of looping.
    if (rtrim($destination, '/') === rtrim(url('/app'), '/')) {
        \Illuminate\Support\Facades\Log::error(
            'app_store_url points at this redirect itself, so /app would loop. '
                . 'Set it to the app store URL in System Settings.',
            ['app_store_url' => $destination]
        );

        abort(404);
    }

    return redirect()->away($destination);
})->name('app.download');

/*
| Inbound SMS from the gateway, so a customer texting STOP is honoured without
| anyone having to read it.
|
| Public because the gateway cannot log in; the secret in the path is what
| protects it, and an unset secret makes the route 404. CSRF-exempt for the
| same reason - see VerifyCsrfToken.
|
| Nothing calls this until TalkSasa is pointed at it. Until then, opt-outs are
| recorded through the admin toggle on the customer.
*/
Route::post('/sms/inbound/{secret}', [SmsController::class, 'inbound'])->name('sms.inbound');

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
            Route::get('/search-customers', [AdminCylinderController::class, 'searchCustomers'])->name('search-customers');

            // Filtered views
            Route::get('/paid-drop-offs', [AdminCylinderController::class, 'paidDropOffs'])->name('paid-drop-offs');
            Route::get('/unpaid-drop-offs', [AdminCylinderController::class, 'unpaidDropOffs'])->name('unpaid-drop-offs');
            Route::get('/pending-payments', [AdminCylinderController::class, 'pendingPayments'])->name('pending-payments');
            Route::get('/advance-collections', [AdminCylinderController::class, 'advanceCollections'])->name('advance-collections');

            // CSV exports of each filtered view, linked from its own screen.
            Route::get('/paid-drop-offs/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'paid-drop-offs')->name('paid-drop-offs.export');
            Route::get('/unpaid-drop-offs/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'unpaid-drop-offs')->name('unpaid-drop-offs.export');
            Route::get('/pending-payments/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'pending-payments')->name('pending-payments.export');
            Route::get('/advance-collections/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'advance-collections')->name('advance-collections.export');

            // Per-customer cylinder history
            Route::get('/customer/{customer}/history', [AdminCylinderController::class, 'customerHistory'])
                ->name('customer.history');
            Route::get('/customer/{customer}/history/export', [AdminCylinderController::class, 'customerHistoryExport'])
                ->name('customer.history.export');

            Route::post('/bulk-update-payment-status', [AdminCylinderController::class, 'bulkUpdatePaymentStatus'])
                ->name('bulk-update-payment-status');

            Route::get('/{cylinder}/receipt', [AdminCylinderController::class, 'receipt'])->name('receipt');
            Route::get('/{cylinder}/edit', [AdminCylinderController::class, 'edit'])->name('edit');
            Route::get('/{cylinder}', [AdminCylinderController::class, 'show'])->name('show');
            Route::put('/{cylinder}', [AdminCylinderController::class, 'update'])->name('update');
            Route::post('/{cylinder}/complete', [AdminCylinderController::class, 'complete'])->name('complete');
            Route::post('/{cylinder}/record-payment', [AdminCylinderController::class, 'recordPayment'])
                ->name('record-payment');
            Route::post('/{cylinder}/cancel', [AdminCylinderController::class, 'cancel'])->name('cancel');
            Route::delete('/{cylinder}', [AdminCylinderController::class, 'destroy'])->name('destroy');
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
        
        // SMS — history, campaigns, and gateway balance
        Route::prefix('sms')->name('sms.')->group(function () {
            Route::get('/', [SmsController::class, 'index'])->name('index');
            Route::get('/compose', [SmsController::class, 'compose'])->name('compose');
            // Costs nothing and sends nothing: what the current filter matches.
            Route::get('/audience-preview', [SmsController::class, 'preview'])->name('audience-preview');
            Route::post('/send', [SmsController::class, 'send'])->name('send');
            Route::post('/opt-out/{customer}', [SmsController::class, 'toggleOptOut'])->name('opt-out');
            Route::get('/balance', [SmsController::class, 'balance'])->name('balance');
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

        // Live availability lookup for the terminal. Reports sellable stock
        // (physical minus reserved). Advisory only - the binding check happens
        // under a row lock when the sale is submitted.
        Route::post('/check-stock', [PosController::class, 'checkStock'])->name('check-stock');
        Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
        // Before sales.show only for readability - the extra segment already
        // makes them unambiguous.
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
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
            Route::get('/search-customers', [AdminCylinderController::class, 'searchCustomers'])->name('search-customers');

            // Filtered views
            Route::get('/paid-drop-offs', [AdminCylinderController::class, 'paidDropOffs'])->name('paid-drop-offs');
            Route::get('/unpaid-drop-offs', [AdminCylinderController::class, 'unpaidDropOffs'])->name('unpaid-drop-offs');
            Route::get('/pending-payments', [AdminCylinderController::class, 'pendingPayments'])->name('pending-payments');
            Route::get('/advance-collections', [AdminCylinderController::class, 'advanceCollections'])->name('advance-collections');

            // CSV exports of each filtered view, linked from its own screen.
            Route::get('/paid-drop-offs/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'paid-drop-offs')->name('paid-drop-offs.export');
            Route::get('/unpaid-drop-offs/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'unpaid-drop-offs')->name('unpaid-drop-offs.export');
            Route::get('/pending-payments/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'pending-payments')->name('pending-payments.export');
            Route::get('/advance-collections/export', [AdminCylinderController::class, 'exportList'])
                ->defaults('list', 'advance-collections')->name('advance-collections.export');

            Route::get('/customer/{customer}/history', [AdminCylinderController::class, 'customerHistory'])
                ->name('customer.history');
            Route::get('/customer/{customer}/history/export', [AdminCylinderController::class, 'customerHistoryExport'])
                ->name('customer.history.export');

            Route::get('/{cylinder}/receipt', [AdminCylinderController::class, 'receipt'])->name('receipt');
            Route::get('/{cylinder}', [AdminCylinderController::class, 'show'])->name('show');
            Route::post('/{cylinder}/complete', [AdminCylinderController::class, 'complete'])->name('complete');
            Route::post('/{cylinder}/record-payment', [AdminCylinderController::class, 'recordPayment'])
                ->name('record-payment');

            // quick-complete / quick-return removed: they pointed at methods
            // that never existed on this controller (they lived on the Pos
            // controller deleted in df0fb52) and returned a 500 on every call.
            // Their old implementation also skipped the reserved-stock commit,
            // so completing through them leaked reserved units permanently.
            // complete() handles both transaction types correctly.
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

/*
|--------------------------------------------------------------------------
| Rider delivery allocation
|--------------------------------------------------------------------------
| Registered under both prefixes for the same reason cylinder management is:
| the POS terminal books cylinders out to a rider and the admin closes the
| order off when the rider returns. Cashiers reach it under /pos, admins
| under /admin, and both hit the same controller.
*/
$riderRoutes = function () {
    Route::get('/', [RiderController::class, 'index'])->name('index');
    Route::post('/', [RiderController::class, 'store'])->name('store');

    // Used by the POS pick-up button
    Route::get('/available', [RiderController::class, 'available'])->name('available');
    Route::post('/allocate', [RiderController::class, 'allocate'])->name('allocate');

    Route::post('/allocations/{allocation}/complete', [RiderController::class, 'complete'])
        ->name('allocations.complete');
    Route::post('/allocations/{allocation}/cancel', [RiderController::class, 'cancel'])
        ->name('allocations.cancel');

    // Parameterised routes last so /available and /allocate are never read as a rider id
    Route::get('/{rider}', [RiderController::class, 'show'])->name('show');
    Route::post('/{rider}/toggle-status', [RiderController::class, 'toggleStatus'])->name('toggle-status');
};

Route::middleware(['auth', 'verified', 'admin.or.cashier'])
    ->prefix('pos/riders')->name('pos.riders.')->group($riderRoutes);

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin/riders')->name('admin.riders.')->group($riderRoutes);

require __DIR__.'/auth.php';