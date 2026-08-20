<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovements,
    ) {
    }

    public function increase(
        Product $product,
        float $quantity,
        string $reason,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->registerMovement($product, 'in', $quantity, $quantity, $reason, $reference, $userId, $notes);
    }

    public function decrease(
        Product $product,
        float $quantity,
        string $reason,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
    ): StockMovement {
        if ((float) $product->current_stock < $quantity) {
            throw new InsufficientStockException(
                "Stock insuficiente para \"{$product->name}\": disponible {$product->current_stock}, solicitado {$quantity}."
            );
        }

        return $this->registerMovement($product, 'out', $quantity, -$quantity, $reason, $reference, $userId, $notes);
    }

    public function adjustTo(
        Product $product,
        float $newQuantity,
        string $reason,
        ?int $userId = null,
        ?string $notes = null,
    ): StockMovement {
        $delta = $newQuantity - (float) $product->current_stock;

        return $this->registerMovement($product, 'adjustment', abs($delta), $delta, $reason, null, $userId, $notes);
    }

    private function registerMovement(
        Product $product,
        string $type,
        float $quantity,
        float $delta,
        string $reason,
        ?Model $reference,
        ?int $userId,
        ?string $notes,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $delta, $reason, $reference, $userId, $notes) {
            if ($delta > 0) {
                $product->increment('current_stock', $delta);
            } elseif ($delta < 0) {
                $product->decrement('current_stock', abs($delta));
            }

            $movement = $this->stockMovements->create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'notes' => $notes,
            ]);

            if ($reference) {
                $movement->reference()->associate($reference)->save();
            }

            return $movement;
        });
    }
}
