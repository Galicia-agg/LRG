<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly OrderService $orderService,
    ) {
    }

    public function index(): Response
    {
        $products = $this->products->activeCatalog();

        return Inertia::render('Storefront/Index', [
            'products' => $products
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'description' => $product->description,
                    'unit' => $product->unit,
                    'sale_price' => (float) $product->sale_price,
                    'images' => $product->images->map(fn ($image) => ['url' => $image->url])->values(),
                    'category' => $product->category?->name,
                    'compatibilities' => $product->compatibilities->map(fn ($c) => [
                        'brand' => $c->brand,
                        'model' => $c->model,
                        'year_from' => $c->year_from,
                        'year_to' => $c->year_to,
                        'engine' => $c->engine,
                    ])->values(),
                ])
                ->values(),
        ]);
    }

    public function show(int $product): Response
    {
        $product = $this->products->findActiveOrFail($product);

        $breadcrumb = [];
        $category = $product->category;

        while ($category) {
            array_unshift($breadcrumb, ['id' => $category->id, 'name' => $category->name]);
            $category = $category->parent;
        }

        return Inertia::render('Storefront/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'sku' => $product->sku,
                'description' => $product->description,
                'unit' => $product->unit,
                'sale_price' => (float) $product->sale_price,
                'in_stock' => (float) $product->current_stock > 0,
                'images' => $product->images->map(fn ($image) => ['url' => $image->url])->values(),
                'category' => $product->category?->name,
                'compatibilities' => $product->compatibilities->map(fn ($c) => [
                    'brand' => $c->brand,
                    'model' => $c->model,
                    'year_from' => $c->year_from,
                    'year_to' => $c->year_to,
                    'engine' => $c->engine,
                ])->values(),
                'specifications' => $product->specifications->map(fn ($s) => [
                    'label' => $s->label,
                    'value' => $s->value,
                ])->values(),
            ],
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->only(['customer_name', 'customer_phone', 'customer_email', 'customer_address']),
                $request->input('items'),
                $request->input('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('storefront.index')->with(
            'success',
            "¡Gracias! Tu pedido #{$order->id} fue recibido. Te contactaremos al {$order->customer_phone} para confirmarlo.",
        );
    }
}
