<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE work_orders ALTER COLUMN reported_issue DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE work_orders SET reported_issue = '' WHERE reported_issue IS NULL");
        DB::statement('ALTER TABLE work_orders ALTER COLUMN reported_issue SET NOT NULL');
    }
};
