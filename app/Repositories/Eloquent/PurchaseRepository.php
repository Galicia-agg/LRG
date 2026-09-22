<?php

namespace App\Repositories\Eloquent;

use App\Models\Purchase;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PurchaseRepository extends BaseRepository implements PurchaseRepositoryInterface
{
    public function __construct(Purchase $model)
    {
        parent::__construct($model);
    }

    public function forDateRange(Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('purchased_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with(['items.product', 'supplier', 'user'])
            ->latest('purchased_at')
            ->get();
    }
}
