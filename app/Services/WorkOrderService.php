<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\CustomerVehicle;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\WorkOrder;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkOrderService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly SaleService $saleService,
    ) {
    }

    /**
     * @param  array<int, int>  $failureIds
     * @param  array<int, int>  $serviceIds
     */
    public function createWorkOrder(
        CustomerVehicle $vehicle,
        ?Mechanic $mechanic,
        ?string $reportedIssue,
        ?float $mileageIn = null,
        ?string $estimatedDeliveryDate = null,
        ?string $notes = null,
        array $failureIds = [],
        string $type = 'reparacion',
        ?string $serviceScope = null,
        array $serviceIds = [],
    ): WorkOrder {
        $workOrder = WorkOrder::query()->create([
            'customer_id' => $vehicle->customer_id,
            'customer_vehicle_id' => $vehicle->id,
            'mechanic_id' => $mechanic?->id,
            'status' => 'recibido',
            'type' => $type,
            'service_scope' => $type === 'servicio' ? $serviceScope : null,
            'mileage_in' => $mileageIn,
            'reported_issue' => $reportedIssue,
            'estimated_delivery_date' => $estimatedDeliveryDate,
            'notes' => $notes,
        ]);

        if (! empty($failureIds)) {
            $workOrder->failures()->sync($failureIds);
        }

        if (! empty($serviceIds)) {
            $workOrder->services()->sync($serviceIds);
        }

        return $workOrder;
    }

    public function toggleFailure(WorkOrder $workOrder, int $failureId): WorkOrder
    {
        $this->assertOpen($workOrder);

        if ($workOrder->failures()->where('common_failures.id', $failureId)->exists()) {
            $workOrder->failures()->detach($failureId);
        } else {
            $workOrder->failures()->attach($failureId);
        }

        return $workOrder->refresh();
    }

    public function toggleService(WorkOrder $workOrder, int $commonServiceId): WorkOrder
    {
        $this->assertOpen($workOrder);

        if ($workOrder->services()->where('common_services.id', $commonServiceId)->exists()) {
            $workOrder->services()->detach($commonServiceId);
        } else {
            $workOrder->services()->attach($commonServiceId);
        }

        return $workOrder->refresh();
    }

    public function addLaborItem(WorkOrder $workOrder, string $description, float $amount): WorkOrder
    {
        $this->assertOpen($workOrder);

        $workOrder->laborItems()->create(['description' => $description, 'amount' => $amount]);

        return $this->recalculateTotals($workOrder);
    }

    public function removeLaborItem(WorkOrder $workOrder, int $laborItemId): WorkOrder
    {
        $this->assertOpen($workOrder);

        $workOrder->laborItems()->whereKey($laborItemId)->delete();

        return $this->recalculateTotals($workOrder);
    }

    public function addPart(WorkOrder $workOrder, int $productId, float $quantity): WorkOrder
    {
        $this->assertOpen($workOrder);

        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $product = $this->products->findOrFail($productId);
        $unitPrice = (float) $product->sale_price;

        $workOrder->parts()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
        ]);

        return $this->recalculateTotals($workOrder);
    }

    public function removePart(WorkOrder $workOrder, int $partId): WorkOrder
    {
        $this->assertOpen($workOrder);

        $workOrder->parts()->whereKey($partId)->delete();

        return $this->recalculateTotals($workOrder);
    }

    public function updateStatus(WorkOrder $workOrder, string $status, ?string $diagnosis = null, ?string $type = null, ?string $serviceScope = null): WorkOrder
    {
        $this->assertOpen($workOrder);

        if (! in_array($status, ['recibido', 'en_proceso', 'listo'], true)) {
            throw new InvalidArgumentException('Estado no válido.');
        }

        if ($type !== null && ! in_array($type, WorkOrder::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de orden no válido.');
        }

        if ($serviceScope !== null && ! in_array($serviceScope, WorkOrder::SERVICE_SCOPES, true)) {
            throw new InvalidArgumentException('Alcance de servicio no válido.');
        }

        $resolvedType = $type ?? $workOrder->type;

        $workOrder->update([
            'status' => $status,
            'diagnosis' => $diagnosis ?? $workOrder->diagnosis,
            'type' => $resolvedType,
            'service_scope' => $resolvedType === 'servicio' ? ($serviceScope ?? $workOrder->service_scope) : null,
        ]);

        return $workOrder->refresh();
    }

    public function cancel(WorkOrder $workOrder, ?string $reason = null): WorkOrder
    {
        $this->assertOpen($workOrder);

        $workOrder->update([
            'status' => 'cancelado',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $workOrder->refresh();
    }

    /**
     * @param  array<int, array{method: string, amount: float}>  $payments
     */
    public function completeAndBill(WorkOrder $workOrder, CashSession $session, User $user, array $payments): WorkOrder
    {
        $this->assertOpen($workOrder);

        if ($workOrder->parts->isEmpty() && (float) $workOrder->labor_total <= 0) {
            throw new InvalidArgumentException('La orden debe tener al menos mano de obra o un repuesto para poder cobrarse.');
        }

        return DB::transaction(function () use ($workOrder, $session, $user, $payments) {
            $customer = $workOrder->customer;

            $items = $workOrder->parts->map(fn ($part) => [
                'product_id' => $part->product_id,
                'quantity' => (float) $part->quantity,
                'unit_price' => (float) $part->unit_price,
            ])->all();

            $sale = $this->saleService->createSale(
                $session,
                $user,
                $customer,
                $items,
                $payments,
                (float) $workOrder->discount,
                (float) $workOrder->labor_total,
                'taller',
            );

            $workOrder->update([
                'status' => 'entregado',
                'sale_id' => $sale->id,
                'delivered_at' => now(),
            ]);

            return $workOrder->refresh();
        });
    }

    private function recalculateTotals(WorkOrder $workOrder): WorkOrder
    {
        $laborTotal = (float) $workOrder->laborItems()->sum('amount');
        $partsTotal = (float) $workOrder->parts()->sum('subtotal');

        $workOrder->update([
            'labor_total' => $laborTotal,
            'parts_total' => $partsTotal,
            'total' => max($laborTotal + $partsTotal - (float) $workOrder->discount, 0),
        ]);

        return $workOrder->refresh();
    }

    private function assertOpen(WorkOrder $workOrder): void
    {
        if (! $workOrder->isOpen()) {
            throw new InvalidArgumentException('Esta orden ya fue entregada o cancelada.');
        }
    }
}
