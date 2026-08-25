<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CommonServiceRepositoryInterface extends RepositoryInterface
{
    public function active(): Collection;
}
