<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function forStatus(?string $status = null): Collection
    {
        return $this->model->newQuery()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['items.product', 'customer'])
            ->latest('created_at')
            ->get();
    }

    public function pendingCount(): int
    {
        return $this->model->newQuery()->where('status', 'pending')->count();
    }
}
