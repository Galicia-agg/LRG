<?php

namespace App\Repositories\Eloquent;

use App\Models\CommonFailure;
use App\Repositories\Contracts\CommonFailureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CommonFailureRepository extends BaseRepository implements CommonFailureRepositoryInterface
{
    public function __construct(CommonFailure $model)
    {
        parent::__construct($model);
    }

    public function active(): Collection
    {
        return $this->model->newQuery()
            ->where('active', true)
            ->orderBy('category')
            ->orderBy('description')
            ->get();
    }
}
