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
        Schema::table('materials', function (Blueprint $table) {
            // Tambahkan kolom 'actualPrice' dan 'standardPrice'
            // Gunakan properti yang sama seperti di migrasi awal
            $table->decimal('actualPrice', 15, 2)->after('item_group');
            $table->decimal('standardPrice', 15, 2)->after('actualPrice');
        });
    }

    /**
     * Kembalikan migrasi.
     * Metode ini menjatuhkan kolom 'actualPrice' dan 'standardPrice'.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Jatuhkan kolom 'actualPrice' dan 'standardPrice' saat rollback
            $table->dropColumn(['actualPrice', 'standardPrice']);
        });
    }
};
