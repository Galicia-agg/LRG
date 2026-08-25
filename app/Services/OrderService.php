<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly SaleService $saleService,
    ) {
    }

    /**
     * @param  array{customer_name: string, customer_phone: string, customer_email?: ?string, customer_address?: ?string, delivery_type?: string}  $customerData
     * @param  array<int, array{product_id: int, quantity: float}>  $items
     */
    public function createOrder(array $customerData, array $items, ?string $notes = null): Order
    {
        if (empty($items)) {
            throw new InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        return DB::transaction(function () use ($customerData, $items, $notes) {
            $subtotal = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $this->products->findOrFail((int) $item['product_id']);
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                if (! $product->active) {
                    throw new InvalidArgumentException("El producto \"{$product->name}\" ya no está disponible.");
                }

                if ($quantity > (float) $product->current_stock) {
                    throw new InvalidArgumentException("No hay suficiente stock de \"{$product->name}\".");
                }

                $unitPrice = (float) $product->sale_price;
                $lineSubtotal = round($quantity * $unitPrice, 2);

                $lines[] = compact('product', 'quantity', 'unitPrice', 'lineSubtotal');
                $subtotal += $lineSubtotal;
            }

            if (empty($lines)) {
                throw new InvalidArgumentException('El pedido debe tener al menos un producto.');
            }

            $deliveryType = $customerData['delivery_type'] ?? 'domicilio';

            $order = Order::query()->create([
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_email' => $customerData['customer_email'] ?? null,
                'customer_address' => $customerData['customer_address'] ?? null,
                'delivery_type' => $deliveryType,
                'delivery_status' => $deliveryType === 'domicilio' ? 'pendiente' : null,
                'notes' => $notes,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => 'pending',
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'subtotal' => $line['lineSubtotal'],
                ]);
            }

            return $order->load('items.product');
        });
    }

    public function confirm(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new InvalidArgumentException('Solo se pueden confirmar pedidos pendientes.');
        }

        $order->update(['status' => 'confirmed']);

        return $order->refresh();
    }

    public function updateDeliveryStatus(Order $order, string $deliveryStatus): Order
    {
        if (! $order->isDelivery()) {
            throw new InvalidArgumentException('Este pedido no es a domicilio.');
        }

        if (! in_array($deliveryStatus, ['pendiente', 'en_camino', 'entregado'], true)) {
            throw new InvalidArgumentException('Estado de entrega no válido.');
        }

        $order->update(['delivery_status' => $deliveryStatus]);

        return $order->refresh();
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->status === 'completed') {
            throw new InvalidArgumentException('No se puede cancelar un pedido ya completado.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $order->refresh();
    }

    public function complete(Order $order, CashSession $session, User $user, string $paymentMethod): Order
    {
        if ($order->status === 'completed') {
            throw new InvalidArgumentException('Este pedido ya fue completado.');
        }

        if ($order->status === 'cancelled') {
            throw new InvalidArgumentException('No se puede completar un pedido cancelado.');
        }

        return DB::transaction(function () use ($order, $session, $user, $paymentMethod) {
            $items = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all();

            $customer = $order->customer_id ? Customer::find($order->customer_id) : null;

            $sale = $this->saleService->createSale(
                $session,
                $user,
                $customer,
                $items,
                [['method' => $paymentMethod, 'amount' => (float) $order->total]],
                0.0,
            );

            $order->update(['status' => 'completed', 'sale_id' => $sale->id]);

            return $order->refresh();
        });
    }
}
