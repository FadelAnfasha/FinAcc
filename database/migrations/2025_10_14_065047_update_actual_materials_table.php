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
        Schema::table('actual_materials', function (Blueprint $table) {

            $table->dropColumn('price');
            $table->decimal('jan_price', 15, 2)->after('item_code');
            $table->decimal('jan_qty', 15, 2)->after('jan_price');
            $table->decimal('feb_price', 15, 2)->after('jan_qty');
            $table->decimal('feb_qty', 15, 2)->after('feb_price');
            $table->decimal('mar_price', 15, 2)->after('feb_qty');
            $table->decimal('mar_qty', 15, 2)->after('mar_price');
            $table->decimal('apr_price', 15, 2)->after('mar_qty');
            $table->decimal('apr_qty', 15, 2)->after('apr_price');
            $table->decimal('may_price', 15, 2)->after('apr_qty');
            $table->decimal('may_qty', 15, 2)->after('may_price');
            $table->decimal('jun_price', 15, 2)->after('may_qty');
            $table->decimal('jun_qty', 15, 2)->after('jun_price');
            $table->decimal('jul_price', 15, 2)->after('jun_qty');
            $table->decimal('jul_qty', 15, 2)->after('jul_price');
            $table->decimal('aug_price', 15, 2)->after('jul_qty');
            $table->decimal('aug_qty', 15, 2)->after('aug_price');
            $table->decimal('sep_price', 15, 2)->after('aug_qty');
            $table->decimal('sep_qty', 15, 2)->after('sep_price');
            $table->decimal('oct_price', 15, 2)->after('sep_qty');
            $table->decimal('oct_qty', 15, 2)->after('oct_price');
            $table->decimal('nov_price', 15, 2)->after('oct_qty');
            $table->decimal('nov_qty', 15, 2)->after('nov_price');
            $table->decimal('dec_price', 15, 2)->after('nov_qty');
            $table->decimal('dec_qty', 15, 2)->after('dec_price');
        });
    }

    public function down(): void
    {
        // Tambahkan logika untuk mengembalikan perubahan
        Schema::table('actual_materials', function (Blueprint $table) {
            // Hapus semua kolom bulanan
            $table->dropColumn([
                'jan_price',
                'jan_qty',
                'feb_price',
                'feb_qty',
                'mar_price',
                'mar_qty',
                'apr_price',
                'apr_qty',
                'may_price',
                'may_qty',
                'jun_price',
                'jun_qty',
                'jul_price',
                'jul_qty',
                'aug_price',
                'aug_qty',
                'sep_price',
                'sep_qty',
                'oct_price',
                'oct_qty',
                'nov_price',
                'nov_qty',
                'dec_price',
                'dec_qty',
            ]);

            // Kembalikan kolom 'price' yang lama
            $table->decimal('price', 15, 2)->nullable();
        });
    }
};
