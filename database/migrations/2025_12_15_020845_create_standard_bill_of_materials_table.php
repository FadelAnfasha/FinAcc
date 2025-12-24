<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Matching\SchemeValidator;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('bom', 'standard_bill_of_materials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('standard_bill_of_materials', 'bom');
    }
};
