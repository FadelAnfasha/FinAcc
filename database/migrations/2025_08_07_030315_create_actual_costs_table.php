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
        Schema::create('actual_cost', function (Blueprint $table) {
            $table->id();
            $table->string('item_code');
            $table->integer('report_year'); // Tambah kolom ini
            $table->integer('report_month'); // Tambah kolom ini

            // Disc
            $table->float('disc_qty')->nullable();
            $table->string('disc_code')->nullable();
            $table->double('disc_price')->nullable();

            // Rim
            $table->float('rim_qty')->nullable();
            $table->string('rim_code')->nullable();
            $table->double('rim_price')->nullable();

            // Sidering
            $table->float('sidering_qty')->nullable();
            $table->string('sidering_code')->nullable();
            $table->double('sidering_price')->nullable();

            // Process Reference Items
            $table->string('pr_disc')->nullable();
            $table->double('pr_disc_price')->nullable();
            $table->string('pr_rim')->nullable();
            $table->double('pr_rim_price')->nullable();
            $table->string('pr_sidering')->nullable();
            $table->double('pr_sidering_price')->nullable();
            $table->string('pr_assy')->nullable();
            $table->double('pr_assy_price')->nullable();
            $table->string('pr_cedW')->nullable();
            $table->double('pr_cedW_price')->nullable();
            $table->string('pr_cedSR')->nullable();
            $table->double('pr_cedSR_price')->nullable();
            $table->string('pr_tcW')->nullable();
            $table->double('pr_tcW_price')->nullable();
            $table->string('pr_tcSR')->nullable();
            $table->double('pr_tcSR_price')->nullable();
            $table->double('pack_price')->nullable();

            // WIP Items
            $table->string('wip_disc')->nullable();
            $table->double('wip_disc_price')->nullable();
            $table->string('wip_rim')->nullable();
            $table->double('wip_rim_price')->nullable();
            $table->string('wip_sidering')->nullable();
            $table->double('wip_sidering_price')->nullable();
            $table->string('wip_assy')->nullable();
            $table->double('wip_assy_price')->nullable();
            $table->string('wip_cedW')->nullable();
            $table->double('wip_cedW_price')->nullable();
            $table->string('wip_cedSR')->nullable();
            $table->double('wip_cedSR_price')->nullable();
            $table->string('wip_tcW')->nullable();
            $table->double('wip_tcW_price')->nullable();
            $table->string('wip_tcSR')->nullable();
            $table->double('wip_tcSR_price')->nullable();
            $table->string('wip_valve')->nullable();
            $table->double('wip_valve_price')->nullable();

            // Summary
            $table->double('total_raw_material')->nullable();
            $table->double('total_process')->nullable();
            $table->double('total')->nullable();
            $table->timestamps();

            $table->unique(columns: ['item_code', 'report_year', 'report_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actual_costs');
    }
};
