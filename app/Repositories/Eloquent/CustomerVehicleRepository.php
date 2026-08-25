<?php

namespace App\Repositories\Eloquent;

use App\Models\CustomerVehicle;
use App\Repositories\Contracts\CustomerVehicleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomerVehicleRepository extends BaseRepository implements CustomerVehicleRepositoryInterface
{
    public function __construct(CustomerVehicle $model)
    {
        parent::__construct($model);
    }

    public function forCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->orderBy('brand')
            ->get();
    }
}
