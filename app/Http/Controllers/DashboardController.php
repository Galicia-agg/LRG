<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $todaySales = Sale::query()->where('status', 'completed')->whereDate('sold_at', today());

        $openCashSession = CashSession::query()
            ->where('user_id', $user->id)
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
            'charts' => [
                'salesTrend' => $user->can('sales.view') ? $this->salesTrend() : null,
                'topProducts' => ($user->can('products.view') || $user->can('products.manage')) ? $this->topProducts() : null,
                'workshopByType' => $user->can('workshop.manage') ? $this->workshopByType() : null,
                'ordersByStatus' => $user->can('orders.manage') ? $this->ordersByStatus() : null,
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, total: float}>
     */
    private function salesTrend(): array
    {
        $from = today()->subDays(13);

        $sales = Sale::query()
            ->where('status', 'completed')
            ->where('sold_at', '>=', $from->copy()->startOfDay())
            ->get(['total', 'sold_at']);

        $byDate = $sales->groupBy(fn (Sale $sale) => $sale->sold_at->toDateString());

        $days = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $from->copy()->addDays($i);
            $key = $date->toDateString();
            $days[] = [
                'label' => $date->isoFormat('D/M'),
                'total' => (float) ($byDate->get($key)?->sum('total') ?? 0),
            ];
        }

        return $days;
    }

    /**
     * @return array<int, array{name: string, quantity: float, revenue: float}>
     */
    private function topProducts(): array
    {
        return SaleItem::query()
            ->whereHas('sale', fn ($query) => $query
                ->where('status', 'completed')
                ->where('sold_at', '>=', now()->subDays(30)))
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->with('product')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product?->name ?? 'Producto eliminado',
                'quantity' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function workshopByType(): array
    {
        return WorkOrder::query()
            ->where('status', '!=', 'cancelado')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function ordersByStatus(): array
    {
        return Order::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }
}
