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
        Schema::create('standard_materials', function (Blueprint $table) {
            $table->string('item_code')->primary();
            $table->integer('in_stock')->nullable();
            $table->string('item_group')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('manufacturer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_materials');
    }
};
