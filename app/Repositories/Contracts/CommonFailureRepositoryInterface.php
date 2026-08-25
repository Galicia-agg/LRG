<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CommonFailureRepositoryInterface extends RepositoryInterface
{
    public function active(): Collection;
}
