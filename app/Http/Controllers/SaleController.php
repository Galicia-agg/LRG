<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CustomerRepositoryInterface $customers,
        private readonly CashSessionRepositoryInterface $cashSessions,
        private readonly SaleRepositoryInterface $sales,
        private readonly SaleService $saleService,
    ) {
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $session = $this->cashSessions->openSessionForUser($request->user()->id);

        if (! $session) {
            return redirect()->route('cash-sessions.create')
                ->with('warning', 'Debes abrir una caja antes de vender.');
        }

        return Inertia::render('Pos/Index', [
            'products' => $this->products->activeCatalog(),
            'customers' => $this->customers->all(),
            'cashSession' => $session,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $session = $this->cashSessions->openSessionForUser($request->user()->id);

        abort_if(! $session, 422, 'No hay una caja abierta.');

        $customer = $request->filled('customer_id')
            ? $this->customers->find((int) $request->input('customer_id'))
            : null;

        try {
            $this->saleService->createSale(
                $session,
                $request->user(),
                $customer,
                $request->input('items'),
                $request->input('payments'),
                (float) $request->input('discount', 0),
            );
        } catch (InsufficientStockException|InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('pos.create')->with('success', 'Venta registrada correctamente.');
    }

    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);

        $sales = $this->sales->forDateRange($from, $to);
        $canViewProfit = $request->user()->can('reports.view');

        return Inertia::render('Sales/Index', [
            'sales' => $sales->values(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => $this->buildSummary($sales, $canViewProfit),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange($request);

        $sales = $this->sales->forDateRange($from, $to);
        $includeProfit = $request->user()->can('reports.view');

        $statusLabels = [
            'completed' => 'Completada',
            'returned' => 'Anulada',
            'cancelled' => 'Cancelada',
        ];

        $filename = "ventas_{$from->toDateString()}_a_{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($sales, $statusLabels, $includeProfit) {
            $handle = fopen('php://output', 'w');

            $header = ['Fecha', 'Hora', 'Venta #', 'Cliente', 'Cajero', 'Subtotal', 'Descuento', 'Total'];
            if ($includeProfit) {
                $header[] = 'Costo';
                $header[] = 'Ganancia';
            }
            $header[] = 'Método(s) de pago';
            $header[] = 'Estado';
            fputcsv($handle, $header);

            foreach ($sales as $sale) {
                $row = [
                    $sale->sold_at->toDateString(),
                    $sale->sold_at->format('H:i'),
                    $sale->id,
                    $sale->customer?->name ?? 'Consumidor final',
                    $sale->user?->name ?? '—',
                    $sale->subtotal,
                    $sale->discount,
                    $sale->total,
                ];

                if ($includeProfit) {
                    $cost = $sale->status === 'completed'
                        ? (float) $sale->items->sum(fn ($item) => (float) $item->cost_price * (float) $item->quantity)
                        : 0.0;

                    $row[] = round($cost, 2);
                    $row[] = $sale->status === 'completed' ? round((float) $sale->total - $cost, 2) : 0.0;
                }

                $row[] = $sale->payments->pluck('method')->implode(', ');
                $row[] = $statusLabels[$sale->status] ?? $sale->status;

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function void(Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->voidSale($sale, auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return back()->with('success', 'Venta anulada correctamente.');
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
            : $to->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function buildSummary(Collection $sales, bool $includeProfit = false): array
    {
        $completed = $sales->where('status', 'completed');
        $voided = $sales->where('status', 'returned');

        $byMethod = $completed
            ->flatMap(fn (Sale $sale) => $sale->payments)
            ->groupBy('method')
            ->map(fn ($payments) => (float) $payments->sum('amount'));

        $byCashier = $completed
            ->groupBy('user_id')
            ->map(fn ($group) => [
                'name' => $group->first()->user?->name ?? '—',
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $byDay = $completed
            ->groupBy(fn (Sale $sale) => $sale->sold_at->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])
            ->sortBy('date')
            ->values();

        $topProducts = $completed
            ->flatMap(fn (Sale $sale) => $sale->items)
            ->groupBy('product_id')
            ->map(function ($group) use ($includeProfit) {
                $entry = [
                    'name' => $group->first()->product?->name ?? '—',
                    'quantity' => (float) $group->sum('quantity'),
                    'revenue' => (float) $group->sum('subtotal'),
                ];

                if ($includeProfit) {
                    $entry['profit'] = (float) $group->sum(
                        fn ($item) => (float) $item->subtotal - ((float) $item->cost_price * (float) $item->quantity),
                    );
                }

                return $entry;
            })
            ->sortByDesc('revenue')
            ->take(8)
            ->values();

        $total = (float) $completed->sum('total');
        $count = $completed->count();

        $summary = [
            'total' => $total,
            'count' => $count,
            'voidedCount' => $voided->count(),
            'averageTicket' => $count > 0 ? round($total / $count, 2) : 0.0,
            'byMethod' => $byMethod,
            'byCashier' => $byCashier,
            'byDay' => $byDay,
            'topProducts' => $topProducts,
        ];

        if ($includeProfit) {
            $cogs = $completed
                ->flatMap(fn (Sale $sale) => $sale->items)
                ->sum(fn ($item) => (float) $item->cost_price * (float) $item->quantity);

            $profit = $total - $cogs;

            $summary['profit'] = $profit;
            $summary['profitMargin'] = $total > 0 ? round(($profit / $total) * 100, 1) : 0.0;
        }

        return $summary;
    }
}
