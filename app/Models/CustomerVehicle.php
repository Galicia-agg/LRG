<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'brand', 'model', 'year', 'plate', 'vin', 'color', 'mileage', 'notes'])]
class CustomerVehicle extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'decimal:1',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function label(): string
    {
        return trim("{$this->brand} {$this->model}".($this->year ? " ({$this->year})" : ''));
    }
}
