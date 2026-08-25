<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Quote;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class QuoteController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CustomerRepositoryInterface $customers,
        private readonly QuoteRepositoryInterface $quotes,
        private readonly QuoteService $quoteService,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Quotes/Create', [
            'products' => $this->products->activeCatalog(),
            'customers' => $this->customers->all(),
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $customer = $request->filled('customer_id')
            ? $this->customers->find((int) $request->input('customer_id'))
            : null;

        try {
            $quote = $this->quoteService->createQuote(
                $request->user(),
                $customer,
                $request->only(['customer_name', 'customer_phone', 'customer_email', 'customer_nit']),
                $request->input('items'),
                (int) $request->input('valid_days'),
                $request->input('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('quotes.show', $quote->id)->with('success', 'Cotización generada correctamente.');
    }

    public function index(): Response
    {
        return Inertia::render('Quotes/Index', [
            'quotes' => $this->quotes->latest()->map(fn (Quote $quote) => [
                'id' => $quote->id,
                'customer_name' => $quote->customer_name,
                'total' => (float) $quote->total,
                'valid_until' => $quote->valid_until->toDateString(),
                'is_expired' => $quote->isExpired(),
                'created_at' => $quote->created_at->toIso8601String(),
                'user' => $quote->user?->name,
                'items_count' => $quote->items->count(),
            ])->values(),
        ]);
    }

    public function show(Quote $quote): Response
    {
        $quote->load(['items.product', 'customer', 'user']);

        return Inertia::render('Quotes/Show', [
            'quote' => [
                'id' => $quote->id,
                'created_at' => $quote->created_at->toIso8601String(),
                'valid_until' => $quote->valid_until->toDateString(),
                'is_expired' => $quote->isExpired(),
                'is_converted' => $quote->isConverted(),
                'sale_id' => $quote->sale_id,
                'seller' => $quote->user?->name ?? '—',
                'customer' => [
                    'name' => $quote->customer_name,
                    'phone' => $quote->customer_phone,
                    'email' => $quote->customer_email,
                    'nit' => $quote->customer_nit,
                ],
                'items' => $quote->items->map(fn ($item) => [
                    'name' => $item->product?->name ?? 'Producto eliminado',
                    'unit' => $item->product?->unit,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ])->values(),
                'subtotal' => (float) $quote->subtotal,
                'discount' => (float) $quote->discount,
                'total' => (float) $quote->total,
                'notes' => $quote->notes,
            ],
            'business' => [
                'name' => config('app.name'),
            ],
        ]);
    }
}
