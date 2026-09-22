<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_id', 'product_id', 'quantity', 'unit_cost', 'subtotal',
    'previous_stock', 'previous_cost_price', 'new_cost_price',
    'previous_sale_price', 'sale_price',
])]
class PurchaseItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'previous_stock' => 'decimal:2',
            'previous_cost_price' => 'decimal:2',
            'new_cost_price' => 'decimal:2',
            'previous_sale_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function marginPercent(): float
    {
        if ((float) $this->sale_price <= 0) {
            return 0.0;
        }

        return round((((float) $this->sale_price - (float) $this->new_cost_price) / (float) $this->sale_price) * 100, 1);
    }
}
