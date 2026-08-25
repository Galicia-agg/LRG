<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface QuoteRepositoryInterface extends RepositoryInterface
{
    public function latest(int $limit = 50): Collection;
}
