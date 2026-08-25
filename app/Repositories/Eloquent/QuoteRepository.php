<?php

namespace App\Repositories\Eloquent;

use App\Models\Quote;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuoteRepository extends BaseRepository implements QuoteRepositoryInterface
{
    public function __construct(Quote $model)
    {
        parent::__construct($model);
    }

    public function latest(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->with(['items.product', 'customer', 'user'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
