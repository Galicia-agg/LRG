<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface StockMovementRepositoryInterface extends RepositoryInterface
{
    public function forProduct(int $productId): Collection;
}
