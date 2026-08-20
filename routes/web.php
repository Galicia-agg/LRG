<?php

use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupplierController;
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

    Route::middleware('permission:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('permission:customers.manage')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('permission:orders.manage')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    });
});

require __DIR__.'/auth.php';
