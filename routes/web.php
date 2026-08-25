<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommonFailureController;
use App\Http\Controllers\CommonServiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerVehicleController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WorkOrderController;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/tienda', [StorefrontController::class, 'index'])->name('storefront.index');
Route::get('/tienda/producto/{product}', [StorefrontController::class, 'show'])->name('storefront.show');
Route::get('/tienda/carrito', [StorefrontController::class, 'cart'])->name('storefront.cart');
Route::post('/tienda/pedido', [StorefrontController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('storefront.store');

Route::get('/dashboard', function () {
    $todaySales = Sale::query()
        ->where('status', 'completed')
        ->whereDate('sold_at', today());

    $openCashSession = CashSession::query()
        ->where('user_id', auth()->id())
        ->where('status', 'open')
        ->latest('opened_at')
        ->first();

    return Inertia::render('Dashboard', [
        'stats' => [
            'salesToday' => [
                'total' => (float) $todaySales->clone()->sum('total'),
                'count' => $todaySales->clone()->count(),
            ],
            'activeProducts' => Product::query()->where('active', true)->count(),
            'lowStockProducts' => Product::query()
                ->where('active', true)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count(),
            'expiringSoonProducts' => Product::query()
                ->where('active', true)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<=', now()->addDays(30))
                ->count(),
            'cashSessionOpen' => $openCashSession !== null,
            'cashSessionOpeningAmount' => $openCashSession?->opening_amount,
            'pendingOrders' => Order::query()->where('status', 'pending')->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('permission:sales.create')->group(function () {
        Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
        Route::post('/pos', [SaleController::class, 'store'])->name('pos.store');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/export', [SaleController::class, 'export'])->name('sales.export');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    });

    Route::middleware('permission:sales.void')->group(function () {
        Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
    });

    Route::middleware('permission:cash-sessions.manage')->group(function () {
        Route::get('/cash-sessions/open', [CashSessionController::class, 'create'])->name('cash-sessions.create');
        Route::post('/cash-sessions', [CashSessionController::class, 'store'])->name('cash-sessions.store');
        Route::get('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'edit'])->name('cash-sessions.edit');
        Route::patch('/cash-sessions/{cashSession}', [CashSessionController::class, 'update'])->name('cash-sessions.update');
    });

    Route::middleware('permission:products.view|products.manage')->group(function () {
        Route::resource('products', ProductController::class)->only(['index']);
    });

    Route::middleware('permission:products.manage')->group(function () {
        Route::resource('products', ProductController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('permission:products.view|products.manage')->group(function () {
        Route::get('/alertas', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('/alertas/stock-bajo/exportar', [AlertController::class, 'exportLowStock'])->name('alerts.export-low-stock');
    });

    Route::middleware('permission:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('permission:customers.manage')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'update', 'destroy']);
    });

    Route::middleware('permission:customers.manage|sales.create')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    });

    Route::middleware('permission:workshop.manage')->group(function () {
        Route::get('/vehiculos', [CustomerVehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/vehiculos', [CustomerVehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehiculos/{vehicle}', [CustomerVehicleController::class, 'show'])->name('vehicles.show');

        Route::get('/mecanicos', [MechanicController::class, 'index'])->name('mechanics.index');
        Route::post('/mecanicos', [MechanicController::class, 'store'])->name('mechanics.store');
        Route::put('/mecanicos/{mechanic}', [MechanicController::class, 'update'])->name('mechanics.update');
        Route::delete('/mecanicos/{mechanic}', [MechanicController::class, 'destroy'])->name('mechanics.destroy');

        Route::get('/fallas-comunes', [CommonFailureController::class, 'index'])->name('common-failures.index');
        Route::post('/fallas-comunes', [CommonFailureController::class, 'store'])->name('common-failures.store');
        Route::put('/fallas-comunes/{commonFailure}', [CommonFailureController::class, 'update'])->name('common-failures.update');
        Route::delete('/fallas-comunes/{commonFailure}', [CommonFailureController::class, 'destroy'])->name('common-failures.destroy');

        Route::get('/servicios-comunes', [CommonServiceController::class, 'index'])->name('common-services.index');
        Route::post('/servicios-comunes', [CommonServiceController::class, 'store'])->name('common-services.store');
        Route::put('/servicios-comunes/{commonService}', [CommonServiceController::class, 'update'])->name('common-services.update');
        Route::delete('/servicios-comunes/{commonService}', [CommonServiceController::class, 'destroy'])->name('common-services.destroy');

        Route::get('/taller', [WorkOrderController::class, 'index'])->name('workshop.index');
        Route::get('/taller/nueva', [WorkOrderController::class, 'create'])->name('workshop.create');
        Route::get('/taller/reportes', [WorkOrderController::class, 'report'])->name('workshop.report');
        Route::post('/taller', [WorkOrderController::class, 'store'])->name('workshop.store');
        Route::get('/taller/{workOrder}', [WorkOrderController::class, 'show'])->name('workshop.show');
        Route::get('/taller/{workOrder}/imprimir', [WorkOrderController::class, 'print'])->name('workshop.print');
        Route::post('/taller/{workOrder}/fallas', [WorkOrderController::class, 'toggleFailure'])->name('workshop.failures.toggle');
        Route::post('/taller/{workOrder}/servicios', [WorkOrderController::class, 'toggleService'])->name('workshop.services.toggle');
        Route::post('/taller/{workOrder}/mano-de-obra', [WorkOrderController::class, 'addLaborItem'])->name('workshop.labor.store');
        Route::delete('/taller/{workOrder}/mano-de-obra/{laborItem}', [WorkOrderController::class, 'removeLaborItem'])->name('workshop.labor.destroy');
        Route::post('/taller/{workOrder}/repuestos', [WorkOrderController::class, 'addPart'])->name('workshop.parts.store');
        Route::delete('/taller/{workOrder}/repuestos/{part}', [WorkOrderController::class, 'removePart'])->name('workshop.parts.destroy');
        Route::patch('/taller/{workOrder}/estado', [WorkOrderController::class, 'updateStatus'])->name('workshop.status');
        Route::post('/taller/{workOrder}/cancelar', [WorkOrderController::class, 'cancel'])->name('workshop.cancel');
    });

    Route::middleware('permission:workshop.manage')
        ->middleware('permission:sales.create')
        ->post('/taller/{workOrder}/completar', [WorkOrderController::class, 'complete'])
        ->name('workshop.complete');

    Route::middleware('permission:orders.manage')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::patch('/orders/{order}/delivery-status', [OrderController::class, 'updateDeliveryStatus'])->name('orders.delivery-status');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    });

    Route::middleware('permission:quotes.manage')->group(function () {
        Route::get('/cotizaciones', [QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/cotizaciones/nueva', [QuoteController::class, 'create'])->name('quotes.create');
        Route::post('/cotizaciones', [QuoteController::class, 'store'])->name('quotes.store');
        Route::get('/cotizaciones/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/configuracion', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/configuracion', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
