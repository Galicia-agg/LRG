<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface PurchaseRepositoryInterface extends RepositoryInterface
{
    public function forDateRange(Carbon $from, Carbon $to): Collection;
}
