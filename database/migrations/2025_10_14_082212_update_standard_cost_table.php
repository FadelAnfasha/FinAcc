<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_cost', function (Blueprint $table) {
            $table->dropUnique(['item_code', 'report_year', 'report_month']);
            $table->string('period')->after('item_code')->nullable();
        });

        $sql = "UPDATE standard_cost SET period = CAST(report_year AS CHAR)";
        DB::statement($sql);

        Schema::table('standard_cost', function (Blueprint $table) {
            $table->dropColumn(['report_year', 'report_month']);
            $table->string('period')->nullable(false)->change();
            $table->unique(['item_code', 'period'], 'standard_cost_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('standard_cost', function (Blueprint $table) {
            $table->dropUnique('standard_cost_period_unique');
            $table->dropColumn('period');
            $table->integer('report_year')->after('item_code');
            $table->integer('report_month')->after('report_year');
            $table->unique(['item_code', 'report_year', 'report_month']);
        });
    }
};
