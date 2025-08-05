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
        Schema::table('sales_quantity', function (Blueprint $table) {
            // Periksa apakah foreign key ada sebelum mencoba menghapusnya.
            // Ini adalah praktik yang baik untuk menghindari error.
            if (Schema::hasColumn('sales_quantity', 'bp_code')) {
                $table->dropForeign(['bp_code']);
            }
            if (Schema::hasColumn('sales_quantity', 'item_code')) {
                $table->dropForeign(['item_code']);
            }
        });
    }

    /**
     * Kembalikan migrasi.
     */
    public function down(): void
    {
        Schema::table('sales_quantity', function (Blueprint $table) {
            // Tambahkan kembali foreign key jika rollback dilakukan.
            // Gunakan nama kolom yang sama seperti sebelumnya.
            $table->foreign('bp_code')->references('bp_code')->on('business_partner')->onDelete('restrict');
            $table->foreign('item_code')->references('item_code')->on('cycle_time')->onDelete('restrict');
        });
    }
};
