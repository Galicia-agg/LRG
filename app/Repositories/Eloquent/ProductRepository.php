<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findOrFail(int $id): Product
    {
        return parent::findOrFail($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['category', 'images'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['category', 'images'])
            ->where(function ($query) use ($term) {
                $query->where('name', 'ilike', "%{$term}%")
                    ->orWhere('sku', 'ilike', "%{$term}%")
                    ->orWhere('barcode', 'ilike', "%{$term}%");
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySkuOrBarcode(string $code): ?Product
    {
        return $this->model->newQuery()
            ->where('sku', $code)
            ->orWhere('barcode', $code)
            ->first();
    }

    public function lowStock(): Collection
    {
        return $this->model->newQuery()
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('active', true)
            ->get();
    }

    public function activeCatalog(): Collection
    {
        return $this->model->newQuery()
            ->where('active', true)
            ->where('current_stock', '>', 0)
            ->with(['category', 'images', 'compatibilities'])
            ->orderBy('name')
            ->get();
    }

    public function findActiveOrFail(int $id): Product
    {
        return $this->model->newQuery()
            ->where('active', true)
            ->with(['category.parent', 'images', 'compatibilities', 'specifications'])
            ->findOrFail($id);
    }
}
