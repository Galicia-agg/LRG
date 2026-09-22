<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryService $inventory,
    ) {
    }

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_cost: float, sale_price?: ?float}>  $items
     */
    public function registerPurchase(
        User $user,
        ?Supplier $supplier,
        array $items,
        ?Carbon $purchasedAt = null,
        ?string $invoiceNumber = null,
        ?string $notes = null,
    ): Purchase {
        if (empty($items)) {
            throw new InvalidArgumentException('La compra debe tener al menos un producto.');
        }

        return DB::transaction(function () use ($user, $supplier, $items, $purchasedAt, $invoiceNumber, $notes) {
            $purchase = Purchase::query()->create([
                'supplier_id' => $supplier?->id,
                'user_id' => $user->id,
                'purchased_at' => $purchasedAt ?? now(),
                'invoice_number' => $invoiceNumber,
                'notes' => $notes,
                'total' => 0,
            ]);

            $total = 0.0;
            $registered = 0;

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitCost = (float) ($item['unit_cost'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $product = $this->products->findOrFail((int) $item['product_id']);

                $previousStock = (float) $product->current_stock;
                $previousCost = (float) $product->cost_price;
                $previousSalePrice = (float) $product->sale_price;

                $totalUnits = $previousStock + $quantity;
                $newCostPrice = $totalUnits > 0
                    ? round((($previousStock * $previousCost) + ($quantity * $unitCost)) / $totalUnits, 2)
                    : round($unitCost, 2);

                $salePrice = array_key_exists('sale_price', $item) && $item['sale_price'] !== null && $item['sale_price'] !== ''
                    ? (float) $item['sale_price']
                    : $previousSalePrice;

                $subtotal = round($quantity * $unitCost, 2);

                $this->inventory->increase($product, $quantity, 'compra', $purchase, $user->id);

                $product->cost_price = $newCostPrice;
                $product->sale_price = $salePrice;
                $product->save();

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'previous_stock' => $previousStock,
                    'previous_cost_price' => $previousCost,
                    'new_cost_price' => $newCostPrice,
                    'previous_sale_price' => $previousSalePrice,
                    'sale_price' => $salePrice,
                ]);

                $total += $subtotal;
                $registered++;
            }

            if ($registered === 0) {
                throw new InvalidArgumentException('La compra debe tener al menos un producto con cantidad válida.');
            }

            $purchase->update(['total' => round($total, 2)]);

            return $purchase->load('items.product', 'supplier', 'user');
        });
    }
}
