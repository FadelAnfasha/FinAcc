<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('process_costs', function (Blueprint $table) {
            $table->string('item_code')->primary();
            // Kolom untuk setiap proses
            $table->decimal('max_of_disc', 15, 2)->default(0);
            $table->decimal('max_of_rim', 15, 2)->default(0);
            $table->decimal('max_of_sidering', 15, 2)->default(0);
            $table->decimal('max_of_assy', 15, 2)->default(0);
            $table->decimal('max_of_ced', 15, 2)->default(0);
            $table->decimal('max_of_topcoat', 15, 2)->default(0);
            $table->decimal('max_of_packaging', 15, 2)->default(0);
            $table->decimal('max_of_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
