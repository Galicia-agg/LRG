<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use App\Models\Setting;
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
                    'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                    'in_stock' => (float) $product->current_stock > 0,
                    'images' => $product->images->map(fn ($image) => ['url' => $image->url])->values(),
                    'category' => $product->category?->name,
                    'is_new' => $product->created_at?->diffInDays(now()) <= 14,
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
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'is_new' => $product->created_at?->diffInDays(now()) <= 14,
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
            'related' => $this->products->relatedTo($product)
                ->map(fn (Product $related) => [
                    'id' => $related->id,
                    'name' => $related->name,
                    'brand' => $related->brand,
                    'unit' => $related->unit,
                    'sale_price' => (float) $related->sale_price,
                    'compare_at_price' => $related->compare_at_price ? (float) $related->compare_at_price : null,
                    'images' => $related->images->map(fn ($image) => ['url' => $image->url])->values(),
                    'category' => $related->category?->name,
                ])
                ->values(),
        ]);
    }

    public function cart(): Response
    {
        return Inertia::render('Storefront/Cart', [
            'deliveryEnabled' => Setting::getBool('delivery_enabled', true),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->only(['customer_name', 'customer_phone', 'customer_email', 'customer_address', 'delivery_type']),
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
