<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly PurchaseService $purchaseService,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/Create', [
            'products' => $this->products->activeCatalog()->map(fn ($product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'current_stock' => (float) $product->current_stock,
                'cost_price' => (float) $product->cost_price,
                'sale_price' => (float) $product->sale_price,
            ])->values(),
            'suppliers' => $this->suppliers->active(),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $supplier = $request->filled('supplier_id')
            ? $this->suppliers->find((int) $request->input('supplier_id'))
            : null;

        try {
            $purchase = $this->purchaseService->registerPurchase(
                $request->user(),
                $supplier,
                $request->input('items'),
                $request->filled('purchased_at') ? Carbon::parse($request->string('purchased_at')->toString()) : null,
                $request->input('invoice_number'),
                $request->input('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', 'Ingreso de mercancía registrado correctamente.');
    }

    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);

        $purchases = $this->purchases->forDateRange($from, $to);

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases->map(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'purchased_at' => $purchase->purchased_at->toIso8601String(),
                'invoice_number' => $purchase->invoice_number,
                'supplier' => $purchase->supplier?->name,
                'user' => $purchase->user?->name,
                'total' => (float) $purchase->total,
                'items_count' => $purchase->items->count(),
                'items' => $purchase->items->map(fn ($item) => [
                    'name' => $item->product?->name ?? 'Producto eliminado',
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ])->values(),
            ])->values(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => $this->buildSummary($purchases),
        ]);
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['items.product', 'supplier', 'user']);

        return Inertia::render('Purchases/Show', [
            'purchase' => [
                'id' => $purchase->id,
                'purchased_at' => $purchase->purchased_at->toIso8601String(),
                'invoice_number' => $purchase->invoice_number,
                'notes' => $purchase->notes,
                'supplier' => $purchase->supplier?->name,
                'registered_by' => $purchase->user?->name ?? '—',
                'total' => (float) $purchase->total,
                'items' => $purchase->items->map(fn ($item) => [
                    'name' => $item->product?->name ?? 'Producto eliminado',
                    'unit' => $item->product?->unit,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                    'previous_stock' => (float) $item->previous_stock,
                    'new_stock' => (float) $item->previous_stock + (float) $item->quantity,
                    'previous_cost_price' => (float) $item->previous_cost_price,
                    'new_cost_price' => (float) $item->new_cost_price,
                    'previous_sale_price' => (float) $item->previous_sale_price,
                    'sale_price' => (float) $item->sale_price,
                    'margin_percent' => $item->marginPercent(),
                ])->values(),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())
            : Carbon::today();

        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())
            : $to->copy()->startOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function buildSummary(Collection $purchases): array
    {
        $byDay = $purchases
            ->groupBy(fn (Purchase $purchase) => $purchase->purchased_at->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])
            ->sortBy('date')
            ->values();

        $topProducts = $purchases
            ->flatMap(fn (Purchase $purchase) => $purchase->items)
            ->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? '—',
                'quantity' => (float) $group->sum('quantity'),
                'spent' => (float) $group->sum('subtotal'),
                'entries' => $group->count(),
            ])
            ->sortByDesc('quantity')
            ->take(8)
            ->values();

        $bySupplier = $purchases
            ->groupBy(fn (Purchase $purchase) => $purchase->supplier?->name ?? 'Sin proveedor')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $byInvoice = $purchases
            ->filter(fn (Purchase $purchase) => filled($purchase->invoice_number))
            ->groupBy('invoice_number')
            ->map(fn ($group, $invoiceNumber) => [
                'invoice_number' => $invoiceNumber,
                'supplier' => $group->first()->supplier?->name ?? 'Sin proveedor',
                'total' => (float) $group->sum('total'),
                'entries' => $group->count(),
                'has_multiple_entries' => $group->count() > 1,
                'items' => $group
                    ->flatMap(fn (Purchase $purchase) => $purchase->items)
                    ->map(fn ($item) => [
                        'name' => $item->product?->name ?? 'Producto eliminado',
                        'quantity' => (float) $item->quantity,
                        'unit_cost' => (float) $item->unit_cost,
                        'subtotal' => (float) $item->subtotal,
                    ])
                    ->values(),
            ])
            ->sortKeys()
            ->values();

        return [
            'total' => (float) $purchases->sum('total'),
            'count' => $purchases->count(),
            'itemsCount' => $purchases->sum(fn (Purchase $purchase) => $purchase->items->count()),
            'byDay' => $byDay,
            'topProducts' => $topProducts,
            'bySupplier' => $bySupplier,
            'byInvoice' => $byInvoice,
        ];
    }
}
