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
        Schema::create('cycle_time', function (Blueprint $table) {
            $table->string('item_code')->unique();
            $table->string('size');
            $table->string('type');
            $table->float('blanking');
            $table->float('blanking_eff');
            $table->float('spinDisc');
            $table->float('spinDisc_eff');
            $table->float('autoDisc');
            $table->float('autoDisc_eff');
            $table->float('manualDisc');
            $table->float('manualDisc_eff');
            $table->float('c3_sn');
            $table->float('c3_sn_eff');
            $table->float('repairC3');
            $table->float('repairC3_eff');
            $table->float('discLathe');
            $table->float('discLathe_eff');
            $table->float('rim1');
            $table->float('rim1_eff');
            $table->float('rim2');
            $table->float('rim2_eff');
            $table->float('rim2insp');
            $table->float('rim2insp_eff');
            $table->float('rim3');
            $table->float('rim3_eff');
            $table->float('coiler');
            $table->float('coiler_eff');
            $table->float('forming');
            $table->float('forming_eff');
            $table->float('assy1');
            $table->float('assy1_eff');
            $table->float('assy2');
            $table->float('assy2_eff');
            $table->float('machining');
            $table->float('machining_eff');
            $table->float('shotPeening');
            $table->float('shotPeening_eff');
            $table->float('ced');
            $table->float('ced_eff');
            $table->float('topcoat');
            $table->float('topcoat_eff');
            $table->float('packing_dom');
            $table->float('packing_exp');
            $table->timestamps();

            $table->index('item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_times');
    }
};
