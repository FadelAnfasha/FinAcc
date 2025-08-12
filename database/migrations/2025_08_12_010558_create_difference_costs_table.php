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
        Schema::create('difference_cost', function (Blueprint $table) {
            $table->id();
            $table->string('item_code'); // Untuk reference total_raw_material,total_process, dan total dari tabel standard_cost maupun actual_cost
            $table->integer('standard_year'); // Untuk reference total_raw_material,total_process, dan total dari tabel standard_cost
            $table->integer('standard_month'); // Untuk reference total_raw_material,total_process, dan total dari tabel standard_cost
            $table->integer('actual_year'); // Untuk reference total_raw_material,total_process, dan total dari tabel actual_cost
            $table->integer('actual_month'); // Untuk reference total_raw_material,total_process, dan total dari tabel actual_cost

            $table->double('total_raw_material')->nullable();
            $table->double('total_process')->nullable();
            $table->double('total')->nullable();
            $table->timestamps();

            $table->unique(
                ['item_code', 'standard_year', 'standard_month', 'actual_year', 'actual_month'],
                'diff_cost_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('difference_cost');
    }
};
