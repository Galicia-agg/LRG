<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('unit_price');
        });

        // Backfill existing rows with the product's current cost, since it
        // wasn't captured at sale time before this column existed.
        DB::statement(
            'UPDATE sale_items SET cost_price = products.cost_price
             FROM products WHERE sale_items.product_id = products.id'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
