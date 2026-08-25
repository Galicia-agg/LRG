<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'cash_session_id', 'user_id', 'customer_id', 'subtotal', 'labor_total',
    'discount', 'total', 'status', 'sold_at',
])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'labor_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function origin(): string
    {
        if ($this->relationLoaded('workOrder') ? $this->workOrder : $this->workOrder()->exists()) {
            return 'taller';
        }

        if ($this->relationLoaded('order') ? $this->order : $this->order()->exists()) {
            return 'tienda';
        }

        return 'mostrador';
    }
}
