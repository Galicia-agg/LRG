<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function search(string $term): Collection;
}
