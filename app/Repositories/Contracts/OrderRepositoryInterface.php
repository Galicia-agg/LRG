<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function forStatus(?string $status = null): Collection;

    public function pendingCount(): int;
}
