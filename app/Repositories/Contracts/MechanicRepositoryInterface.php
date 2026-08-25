<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface MechanicRepositoryInterface extends RepositoryInterface
{
    public function active(): Collection;
}
