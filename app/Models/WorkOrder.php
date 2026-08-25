<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id', 'customer_vehicle_id', 'mechanic_id', 'sale_id', 'status', 'type', 'service_scope',
    'mileage_in', 'reported_issue', 'diagnosis', 'estimated_delivery_date',
    'delivered_at', 'labor_total', 'parts_total', 'discount', 'total', 'notes',
    'cancelled_at', 'cancellation_reason',
])]
class WorkOrder extends Model
{
    public const STATUSES = ['recibido', 'en_proceso', 'listo', 'entregado', 'cancelado'];

    public const TYPES = ['servicio', 'reparacion'];

    public const SERVICE_SCOPES = ['menor', 'mayor'];

    protected function casts(): array
    {
        return [
            'mileage_in' => 'decimal:1',
            'estimated_delivery_date' => 'date',
            'delivered_at' => 'datetime',
            'labor_total' => 'decimal:2',
            'parts_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'customer_vehicle_id');
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function laborItems(): HasMany
    {
        return $this->hasMany(WorkOrderLaborItem::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function failures(): BelongsToMany
    {
        return $this->belongsToMany(CommonFailure::class, 'work_order_common_failures');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(CommonService::class, 'work_order_common_services');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['entregado', 'cancelado'], true);
    }

    public function isService(): bool
    {
        return $this->type === 'servicio';
    }
}
