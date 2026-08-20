<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories,
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();

        $products = $search !== ''
            ? $this->products->search($search)
            : $this->products->paginate();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => $this->categories->all(),
            'product' => null,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialStock = (float) $data['current_stock'];
        $data['current_stock'] = 0;

        $images = $data['images'] ?? [];
        $compatibilities = $data['compatibilities'] ?? [];
        $specifications = $data['specifications'] ?? [];
        unset($data['images'], $data['compatibilities'], $data['specifications']);

        $product = $this->products->create($data);

        $this->storeImages($product, $images, 0);
        $this->syncCompatibilities($product, $compatibilities);
        $this->syncSpecifications($product, $specifications);

        if ($initialStock > 0) {
            $this->inventory->adjustTo(
                $product,
                $initialStock,
                'inventario_inicial',
                $request->user()->id,
            );
        }

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => $this->categories->all(),
            'product' => $product->load(['images', 'compatibilities', 'specifications']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $newImages = $data['images'] ?? [];
        $removeImageIds = $data['remove_image_ids'] ?? [];
        $compatibilities = $data['compatibilities'] ?? [];
        $specifications = $data['specifications'] ?? [];
        unset($data['images'], $data['remove_image_ids'], $data['compatibilities'], $data['specifications']);

        $this->products->update($product, $data);

        if (! empty($removeImageIds)) {
            $toRemove = $product->images()->whereIn('id', $removeImageIds)->get();

            foreach ($toRemove as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        $nextOrder = (int) $product->images()->max('sort_order') + 1;
        $this->storeImages($product, $newImages, $nextOrder);

        $this->syncCompatibilities($product, $compatibilities);
        $this->syncSpecifications($product, $specifications);

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->update($product, ['active' => false]);

        return redirect()->route('products.index')->with('success', 'Producto desactivado.');
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    private function storeImages(Product $product, array $files, int $startOrder): void
    {
        foreach (array_values($files) as $index => $file) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $file->store('products', 'public'),
                'sort_order' => $startOrder + $index,
            ]);
        }
    }

    /**
     * @param  array<int, array{brand: string, model: string, year_from?: ?int, year_to?: ?int, engine?: ?string}>  $rows
     */
    private function syncCompatibilities(Product $product, array $rows): void
    {
        $product->compatibilities()->delete();

        foreach ($rows as $row) {
            $product->compatibilities()->create([
                'brand' => $row['brand'],
                'model' => $row['model'],
                'year_from' => $row['year_from'] ?? null,
                'year_to' => $row['year_to'] ?? null,
                'engine' => $row['engine'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $rows
     */
    private function syncSpecifications(Product $product, array $rows): void
    {
        $product->specifications()->delete();

        foreach (array_values($rows) as $index => $row) {
            $product->specifications()->create([
                'label' => $row['label'],
                'value' => $row['value'],
                'sort_order' => $index,
            ]);
        }
    }
}
