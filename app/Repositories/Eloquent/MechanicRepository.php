<?php

namespace App\Repositories\Eloquent;

use App\Models\Mechanic;
use App\Repositories\Contracts\MechanicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MechanicRepository extends BaseRepository implements MechanicRepositoryInterface
{
    public function __construct(Mechanic $model)
    {
        parent::__construct($model);
    }

    public function active(): Collection
    {
        return $this->model->newQuery()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }
}
