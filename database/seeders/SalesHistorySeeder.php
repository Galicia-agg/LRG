<?php

namespace Database\Seeders;

use App\Exceptions\InsufficientStockException;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Backfills believable sales history for the last few weeks so reports,
 * dashboards, and demos have data to show. Safe to re-run: every run adds
 * another batch of historical days on top of whatever already exists.
 */
class SalesHistorySeeder extends Seeder
{
    /** @var array<int, float> product_id => remaining stock during simulation */
    private array $stock = [];

    public function run(): void
    {
        $cashiers = User::whereIn('email', ['cajero@aceitera.test', 'admin@aceitera.test'])->get();
        $products = Product::where('active', true)->get();
        $customers = Customer::all();

        if ($cashiers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('No hay usuarios o productos activos; se omite la carga de historial de ventas.');

            return;
        }

        $originalStock = $products->pluck('current_stock', 'id')->map(fn ($qty) => (float) $qty)->all();
        $this->stock = $originalStock;

        /** @var SaleService $saleService */
        $saleService = app(SaleService::class);
        /** @var SaleRepositoryInterface $saleRepository */
        $saleRepository = app(SaleRepositoryInterface::class);
        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        $salesGenerated = 0;
        $businessDayIndex = 0;

        for ($daysAgo = 28; $daysAgo >= 1; $daysAgo--) {
            $day = Carbon::today()->subDays($daysAgo);

            if ($day->isSunday()) {
                continue; // el taller cierra los domingos
            }

            $businessDayIndex++;

            $cashier = $cashiers->count() > 1 && random_int(1, 100) <= 25
                ? $cashiers->firstWhere('email', 'admin@aceitera.test')
                : $cashiers->firstWhere('email', 'cajero@aceitera.test');
            $cashier ??= $cashiers->first();

            // Simulate a supplier delivery every ~5 business days so stock
            // doesn't dry up halfway through the simulated month.
            if ($businessDayIndex % 5 === 1) {
                $restockedAt = $day->copy()->setTime(7, 30);

                foreach ($products as $product) {
                    $restockQty = max(2, (int) round(($originalStock[$product->id] ?? 10) * 0.5));
                    $movement = $inventoryService->increase($product, $restockQty, 'compra', null, $cashier->id);

                    DB::table('stock_movements')->where('id', $movement->id)
                        ->update(['created_at' => $restockedAt, 'updated_at' => $restockedAt]);

                    $this->stock[$product->id] = ($this->stock[$product->id] ?? 0) + $restockQty;
                }
            }

            $openingAmount = random_int(6, 16) * 25; // Q150 - Q400
            $openedAt = $day->copy()->setTime(8, 0);

            $session = CashSession::create([
                'user_id' => $cashier->id,
                'opening_amount' => $openingAmount,
                'status' => 'open',
                'opened_at' => $openedAt,
            ]);

            DB::table('cash_sessions')->where('id', $session->id)
                ->update(['created_at' => $openedAt, 'updated_at' => $openedAt]);

            $saleTimes = collect(range(1, random_int(3, 7)))
                ->map(fn () => $day->copy()->setTime(random_int(8, 18), random_int(0, 59)))
                ->sort()
                ->values();

            foreach ($saleTimes as $soldAt) {
                $sale = $this->createRandomSale($saleService, $session, $cashier, $products, $customers, $soldAt);

                if (! $sale) {
                    continue;
                }

                $salesGenerated++;

                // ~8% of sales end up voided later that day, like a real return.
                if (random_int(1, 100) <= 8) {
                    $voidedAt = $soldAt->copy()->addMinutes(random_int(5, 120));

                    try {
                        $saleService->voidSale($sale, $cashier->id);

                        DB::table('sales')->where('id', $sale->id)->update(['updated_at' => $voidedAt]);
                        DB::table('stock_movements')
                            ->where('reference_type', Sale::class)
                            ->where('reference_id', $sale->id)
                            ->where('type', 'in')
                            ->update(['created_at' => $voidedAt, 'updated_at' => $voidedAt]);
                    } catch (InvalidArgumentException) {
                        // leave it as completed
                    }
                }
            }

            $expected = $openingAmount + $saleRepository->totalCashForSession($session->id);
            $closingAmount = max(0, $expected + random_int(-15, 15));
            $closedAt = $day->copy()->setTime(19, 0);

            DB::table('cash_sessions')->where('id', $session->id)->update([
                'closing_amount' => $closingAmount,
                'expected_amount' => $expected,
                'difference' => $closingAmount - $expected,
                'status' => 'closed',
                'closed_at' => $closedAt,
                'updated_at' => $closedAt,
            ]);
        }

        $this->command?->info("Historial de ventas generado: {$salesGenerated} ventas simuladas.");
    }

    private function createRandomSale(
        SaleService $saleService,
        CashSession $session,
        User $cashier,
        Collection $products,
        Collection $customers,
        Carbon $soldAt,
    ): ?Sale {
        $lineCount = random_int(1, 3);
        $items = [];

        foreach ($products->shuffle() as $product) {
            if (count($items) >= $lineCount) {
                break;
            }

            $available = $this->stock[$product->id] ?? 0.0;

            if ($available < 1) {
                continue;
            }

            $isBulk = in_array($product->unit, ['litro', 'galon', 'cuarto'], true);
            $desired = $isBulk ? random_int(1, 4) : random_int(1, 3);
            $quantity = min($desired, (int) floor($available));

            if ($quantity < 1) {
                continue;
            }

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ];

            $this->stock[$product->id] = $available - $quantity;
        }

        if (empty($items)) {
            return null;
        }

        $customer = $customers->isNotEmpty() && random_int(1, 100) <= 30
            ? $customers->random()
            : null;

        $subtotal = collect($items)->sum(
            fn ($item) => (float) $products->firstWhere('id', $item['product_id'])->sale_price * $item['quantity'],
        );

        $discount = random_int(1, 100) <= 15 ? round($subtotal * (random_int(5, 15) / 100), 2) : 0.0;
        $total = round($subtotal - $discount, 2);

        $methodRoll = random_int(1, 100);
        $method = $methodRoll <= 70 ? 'efectivo' : ($methodRoll <= 90 ? 'tarjeta' : 'transferencia');

        try {
            $sale = $saleService->createSale(
                $session,
                $cashier,
                $customer,
                $items,
                [['method' => $method, 'amount' => $total]],
                $discount,
            );
        } catch (InsufficientStockException|InvalidArgumentException) {
            return null;
        }

        DB::table('sales')->where('id', $sale->id)->update([
            'sold_at' => $soldAt,
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);

        DB::table('sale_items')->where('sale_id', $sale->id)
            ->update(['created_at' => $soldAt, 'updated_at' => $soldAt]);

        DB::table('payments')->where('sale_id', $sale->id)
            ->update(['created_at' => $soldAt, 'updated_at' => $soldAt]);

        DB::table('stock_movements')
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->update(['created_at' => $soldAt, 'updated_at' => $soldAt]);

        return $sale->fresh();
    }
}
