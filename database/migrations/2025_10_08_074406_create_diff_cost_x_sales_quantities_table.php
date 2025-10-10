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
        Schema::create('diff_cost_x_sales_quantities', function (Blueprint $table) {
            $table->id();
            $table->string('item_code');
            $table->string('difference_period');
            $table->string('sales_month');
            $table->integer('quantity');
            $table->double('total_raw_material')->nullable();
            $table->double('total_process')->nullable();
            $table->double('total')->nullable();
            $table->timestamps();

            $table->unique(
                ['item_code', 'difference_period', 'sales_month'],
                'diff_cost_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diff_cost_x_sales_qties');
    }
};
