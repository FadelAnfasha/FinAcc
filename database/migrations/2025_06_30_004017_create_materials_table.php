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
        Schema::create('materials', function (Blueprint $table) {
            $table->string('item_code')->primary();
            $table->integer('in_stock')->nullable();
            $table->string('item_group')->nullable();
            $table->decimal('actualPrice', 15, 2)->nullable();
            $table->decimal('standardPrice', 15, 2)->nullable();
            $table->string('manufacturer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Kembalikan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
