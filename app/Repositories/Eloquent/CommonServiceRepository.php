<?php

namespace App\Repositories\Eloquent;

use App\Models\CommonService;
use App\Repositories\Contracts\CommonServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CommonServiceRepository extends BaseRepository implements CommonServiceRepositoryInterface
{
    public function __construct(CommonService $model)
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
