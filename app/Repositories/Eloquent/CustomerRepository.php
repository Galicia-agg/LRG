<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function search(string $term): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'ilike', "%{$term}%")
            ->orWhere('nit', 'ilike', "%{$term}%")
            ->limit(20)
            ->get();
    }
}
