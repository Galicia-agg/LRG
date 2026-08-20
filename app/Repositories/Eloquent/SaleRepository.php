<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SaleRepository extends BaseRepository implements SaleRepositoryInterface
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    public function paginateForSession(int $cashSessionId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('cash_session_id', $cashSessionId)
            ->with(['items.product', 'payments', 'customer'])
            ->latest('sold_at')
            ->paginate($perPage);
    }

    public function totalForSession(int $cashSessionId): float
    {
        return (float) $this->model->newQuery()
            ->where('cash_session_id', $cashSessionId)
            ->where('status', 'completed')
            ->sum('total');
    }

    public function totalCashForSession(int $cashSessionId): float
    {
        return (float) Payment::query()
            ->where('method', 'efectivo')
            ->whereHas('sale', function ($query) use ($cashSessionId) {
                $query->where('cash_session_id', $cashSessionId)
                    ->where('status', 'completed');
            })
            ->sum('amount');
    }

    public function forDateRange(Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with(['items.product', 'payments', 'customer', 'user'])
            ->latest('sold_at')
            ->get();
    }
}
