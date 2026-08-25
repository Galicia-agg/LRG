<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        private readonly SaleRepositoryInterface $sales,
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryService $inventory,
    ) {
    }

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price?: float, discount?: float}>  $items
     * @param  array<int, array{method: string, amount: float}>  $payments
     */
    public function createSale(
        CashSession $session,
        User $user,
        ?Customer $customer,
        array $items,
        array $payments,
        float $discount = 0,
        float $laborTotal = 0,
        string $stockMovementReason = 'venta',
    ): Sale {
        if (empty($items) && $laborTotal <= 0) {
            throw new InvalidArgumentException('La venta debe tener al menos un producto.');
        }

        if (! $session->isOpen()) {
            throw new InvalidArgumentException('La caja indicada no está abierta.');
        }

        return DB::transaction(function () use ($session, $user, $customer, $items, $payments, $discount, $laborTotal, $stockMovementReason) {
            $subtotal = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $this->products->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price);
                $lineDiscount = (float) ($item['discount'] ?? 0);
                $lineSubtotal = ($quantity * $unitPrice) - $lineDiscount;

                $lines[] = compact('product', 'quantity', 'unitPrice', 'lineDiscount', 'lineSubtotal');
                $subtotal += $lineSubtotal;
            }

            $total = $subtotal + $laborTotal - $discount;
            $paymentsTotal = array_sum(array_column($payments, 'amount'));

            if (abs($paymentsTotal - $total) > 0.01) {
                throw new InvalidArgumentException('El total de los pagos no coincide con el total de la venta.');
            }

            $sale = Sale::query()->create([
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'subtotal' => $subtotal,
                'labor_total' => $laborTotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'completed',
                'sold_at' => now(),
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'cost_price' => $line['product']->cost_price,
                    'discount' => $line['lineDiscount'],
                    'subtotal' => $line['lineSubtotal'],
                ]);

                $this->inventory->decrease(
                    $line['product'],
                    $line['quantity'],
                    $stockMovementReason,
                    $sale,
                    $user->id,
                );
            }

            foreach ($payments as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                ]);
            }

            return $sale->load(['items.product', 'payments', 'customer']);
        });
    }

    public function voidSale(Sale $sale, ?int $userId = null): Sale
    {
        if ($sale->status !== 'completed') {
            throw new InvalidArgumentException('Solo se pueden anular ventas completadas.');
        }

        return DB::transaction(function () use ($sale, $userId) {
            foreach ($sale->items as $item) {
                $this->inventory->increase(
                    $item->product,
                    (float) $item->quantity,
                    'devolucion',
                    $sale,
                    $userId,
                );
            }

            $sale->update(['status' => 'returned']);

            return $sale->refresh();
        });
    }
}
