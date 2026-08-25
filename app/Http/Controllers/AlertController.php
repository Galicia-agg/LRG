<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Csv;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Alerts/Index', [
            'lowStock' => $this->products->lowStock()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'current_stock' => (float) $product->current_stock,
                    'min_stock' => (float) $product->min_stock,
                ])
                ->values(),
            'expiringSoon' => $this->products->expiringSoon()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => (float) $product->current_stock,
                    'expiration_date' => $product->expiration_date->toDateString(),
                    'is_expired' => $product->isExpired(),
                ])
                ->values(),
        ]);
    }

    public function exportLowStock(): StreamedResponse
    {
        $products = $this->products->lowStock();
        $filename = 'stock_bajo_'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel detects UTF-8 and renders accented characters correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['SKU', 'Producto', 'Unidad', 'Stock actual', 'Stock mínimo', 'Faltante']);

            foreach ($products as $product) {
                fputcsv($handle, Csv::safeRow([
                    $product->sku,
                    $product->name,
                    $product->unit,
                    $product->current_stock,
                    $product->min_stock,
                    max(0, (float) $product->min_stock - (float) $product->current_stock),
                ]));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
