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
        Schema::rename('valves', 'standard_consumables');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('standard_consumables', 'valves');
    }
};
