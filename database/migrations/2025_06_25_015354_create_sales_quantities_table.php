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
        Schema::create('sales_quantity', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code');
            $table->string('item_code');
            $table->integer('quantity');
            $table->timestamps();

            // $table->foreign('bp_code')->references('bp_code')->on('business_partner')->onDelete('restrict');
            // $table->foreign('item_code')->references('item_code')->on('cycle_time')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quantities');
    }
};
