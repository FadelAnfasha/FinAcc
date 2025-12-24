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
        Schema::create('actual_bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->string('item_code');
            $table->string('description');
            $table->string('uom')->nullable();
            $table->float('quantity');
            $table->string('warehouse');
            $table->integer('depth');
            $table->string('bom_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actual_bill_of_materials');
    }
};
