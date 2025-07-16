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
        Schema::create('wages_distribution', function (Blueprint $table) {
            $table->decimal('blanking', 15, 2);
            $table->decimal('spinDisc', 15, 2);
            $table->decimal('autoDisc', 15, 2);
            $table->decimal('manualDisc', 15, 2);
            $table->decimal('discLathe', 15, 2);
            $table->decimal('rim1', 15, 2);
            $table->decimal('rim2', 15, 2);
            $table->decimal('rim3', 15, 2);
            $table->decimal('coiler', 15, 2);
            $table->decimal('forming', 15, 2);
            $table->decimal('assy1', 15, 2);
            $table->decimal('assy2', 15, 2);
            $table->decimal('machining', 15, 2);
            $table->decimal('shotPeening', 15, 2);
            $table->decimal('ced', 15, 2);
            $table->decimal('topcoat', 15, 2);
            $table->decimal('packing_dom', 15, 2);
            $table->decimal('packing_exp', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wages_distributions');
    }
};
