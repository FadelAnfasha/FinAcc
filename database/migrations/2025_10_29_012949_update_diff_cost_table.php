<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE difference_cost SET period = REPLACE(period, 'YTM', 'YTD') WHERE period LIKE '%YTM%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE difference_cost SET period = REPLACE(period, 'YTD', 'YTM') WHERE period LIKE '%YTD%'");
    }
};
