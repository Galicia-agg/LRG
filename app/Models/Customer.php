<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'nit', 'phone', 'email', 'address'])]
class Customer extends Model
{
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
