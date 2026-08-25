<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class WorkOrderRepository extends BaseRepository implements WorkOrderRepositoryInterface
{
    public function __construct(WorkOrder $model)
    {
        parent::__construct($model);
    }

    public function forStatus(?string $status = null, ?int $failureId = null, ?string $type = null, ?string $serviceScope = null): Collection
    {
        return $this->model->newQuery()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($serviceScope, fn ($query) => $query->where('service_scope', $serviceScope))
            ->when($failureId, fn ($query) => $query->whereHas(
                'failures',
                fn ($q) => $q->where('common_failures.id', $failureId),
            ))
            ->with(['customer', 'vehicle', 'mechanic', 'failures'])
            ->latest('created_at')
            ->get();
    }

    public function openCount(): int
    {
        return $this->model->newQuery()
            ->whereNotIn('status', ['entregado', 'cancelado'])
            ->count();
    }

    public function forDateRange(Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with(['customer', 'vehicle', 'mechanic', 'laborItems', 'parts.product', 'services'])
            ->latest('created_at')
            ->get();
    }
}
