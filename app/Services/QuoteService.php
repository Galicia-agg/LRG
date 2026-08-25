<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuoteService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    /**
     * @param  array{customer_name: string, customer_phone?: ?string, customer_email?: ?string, customer_nit?: ?string}  $customerData
     * @param  array<int, array{product_id: int, quantity: float}>  $items
     */
    public function createQuote(
        User $user,
        ?Customer $customer,
        array $customerData,
        array $items,
        int $validDays,
        ?string $notes = null,
    ): Quote {
        if (empty($items)) {
            throw new InvalidArgumentException('La cotización debe tener al menos un producto.');
        }

        return DB::transaction(function () use ($user, $customer, $customerData, $items, $validDays, $notes) {
            $subtotal = 0.0;
            $lines = [];

            foreach ($items as $item) {
                $product = $this->products->findOrFail((int) $item['product_id']);
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $unitPrice = (float) $product->sale_price;
                $lineSubtotal = round($quantity * $unitPrice, 2);

                $lines[] = compact('product', 'quantity', 'unitPrice', 'lineSubtotal');
                $subtotal += $lineSubtotal;
            }

            if (empty($lines)) {
                throw new InvalidArgumentException('La cotización debe tener al menos un producto.');
            }

            $quote = Quote::query()->create([
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'] ?? null,
                'customer_email' => $customerData['customer_email'] ?? null,
                'customer_nit' => $customerData['customer_nit'] ?? null,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'valid_until' => now()->addDays($validDays)->toDateString(),
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $quote->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'subtotal' => $line['lineSubtotal'],
                ]);
            }

            return $quote->load('items.product', 'customer');
        });
    }
}
