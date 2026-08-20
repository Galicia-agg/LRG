<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function active(): Collection;
}
