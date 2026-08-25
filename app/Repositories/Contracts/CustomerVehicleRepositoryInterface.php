<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CustomerVehicleRepositoryInterface extends RepositoryInterface
{
    public function forCustomer(int $customerId): Collection;
}
