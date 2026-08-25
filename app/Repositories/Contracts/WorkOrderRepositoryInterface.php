<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface WorkOrderRepositoryInterface extends RepositoryInterface
{
    public function forStatus(?string $status = null, ?int $failureId = null, ?string $type = null, ?string $serviceScope = null): Collection;

    public function openCount(): int;

    public function forDateRange(Carbon $from, Carbon $to): Collection;
}
