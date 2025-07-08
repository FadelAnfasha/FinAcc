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
        Schema::create('cost_per_processes', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code');
            $table->string('item_code');
            $table->float('blanking');
            $table->float('spinDisc');
            $table->float('autoDisc');
            $table->float('manualDisc');
            $table->float('discLathe');
            $table->float('total_disc');

            $table->float('rim1');
            $table->float('rim2');
            $table->float('rim3');
            $table->float('total_rim');

            $table->float('coiler');
            $table->float('forming');
            $table->float('total_sidering');

            $table->float('assy1');
            $table->float('assy2');
            $table->float('machining');
            $table->float('shotPeening');
            $table->float('total_assy');

            $table->float('ced');
            $table->float('topcoat');
            $table->float('total_painting');

            $table->float('packing_dom');
            $table->float('packing_exp');
            $table->float('total_packaging');

            $table->float('total');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_per_processes');
    }
};
