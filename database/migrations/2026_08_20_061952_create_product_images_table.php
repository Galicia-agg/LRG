<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Carry over the single image every product had before the gallery existed.
        if (Schema::hasColumn('products', 'image_path')) {
            $now = now();

            DB::table('products')
                ->whereNotNull('image_path')
                ->get(['id', 'image_path'])
                ->each(function ($product) use ($now) {
                    DB::table('product_images')->insert([
                        'product_id' => $product->id,
                        'path' => $product->image_path,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('description');
            });

            DB::table('product_images')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('product_id')
                ->each(function ($images, $productId) {
                    DB::table('products')->where('id', $productId)->update([
                        'image_path' => $images->first()->path,
                    ]);
                });
        }

        Schema::dropIfExists('product_images');
    }
};
