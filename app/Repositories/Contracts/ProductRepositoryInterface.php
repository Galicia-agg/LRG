<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function search(string $term, int $perPage = 15): LengthAwarePaginator;

    public function findBySkuOrBarcode(string $code): ?Product;

    public function lowStock(): Collection;

    public function expiringSoon(int $days = 30): Collection;

    public function activeCatalog(): Collection;

    public function findActiveOrFail(int $id): Product;

    public function relatedTo(Product $product, int $limit = 4): Collection;
}
