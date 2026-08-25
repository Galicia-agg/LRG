<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['description', 'category', 'suggested_price', 'active'])]
class CommonFailure extends Model
{
    protected function casts(): array
    {
        return [
            'suggested_price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
