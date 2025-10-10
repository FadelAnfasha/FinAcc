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
        Schema::create('actual_salesquantities', function (Blueprint $table) {
            $table->string('item_code')->primary();
            $table->integer('jan_qty')->nullable();
            $table->integer('feb_qty')->nullable();
            $table->integer('mar_qty')->nullable();
            $table->integer('apr_qty')->nullable();
            $table->integer('may_qty')->nullable();
            $table->integer('jun_qty')->nullable();
            $table->integer('jul_qty')->nullable();
            $table->integer('aug_qty')->nullable();
            $table->integer('sep_qty')->nullable();
            $table->integer('oct_qty')->nullable();
            $table->integer('nov_qty')->nullable();
            $table->integer('dec_qty')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
