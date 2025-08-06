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
            // Periksa apakah kolom 'price' ada sebelum mencoba menghapusnya.
            if (Schema::hasColumn('materials', 'price')) {
                $table->dropColumn('price');
            }

            // Periksa apakah kolom-kolom baru sudah ada sebelum menambahkannya.
            // Ini mencegah error jika migrasi ini dijalankan lebih dari sekali.
            if (!Schema::hasColumn('materials', 'actualPrice')) {
                $table->decimal('actualPrice', 15, 2)->after('item_group')->nullable();
            }
            if (!Schema::hasColumn('materials', 'standardPrice')) {
                $table->decimal('standardPrice', 15, 2)->after('actualPrice')->nullable();
            }
        });
    }

    /**
     * Kembalikan migrasi.
     * Saat rollback, kolom 'actualPrice' dan 'standardPrice' akan dihapus,
     * dan kolom 'price' akan ditambahkan kembali (jika diperlukan).
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['actualPrice', 'standardPrice']);

            // Tambahkan kembali kolom 'price' yang lama jika diperlukan saat rollback
            // $table->decimal('price', 15, 2)->nullable()->after('item_group');
        });
    }
};
