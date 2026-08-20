<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface SaleRepositoryInterface extends RepositoryInterface
{
    public function paginateForSession(int $cashSessionId, int $perPage = 15): LengthAwarePaginator;

    public function totalForSession(int $cashSessionId): float;

    public function totalCashForSession(int $cashSessionId): float;

    public function forDateRange(Carbon $from, Carbon $to): Collection;
}
