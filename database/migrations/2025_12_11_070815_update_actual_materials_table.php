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
        Schema::table('actual_materials', function (Blueprint $table) {
            $table->dropPrimary('item_code');
            $table->id()->first();
            $table->string('period', 4)->after('item_code')->nullable();
            $table->renameColumn('jan_price', 'jan_amount');
            $table->renameColumn('feb_price', 'feb_amount');
            $table->renameColumn('mar_price', 'mar_amount');
            $table->renameColumn('apr_price', 'apr_amount');
            $table->renameColumn('may_price', 'may_amount');
            $table->renameColumn('jun_price', 'jun_amount');
            $table->renameColumn('jul_price', 'jul_amount');
            $table->renameColumn('aug_price', 'aug_amount');
            $table->renameColumn('sep_price', 'sep_amount');
            $table->renameColumn('oct_price', 'oct_amount');
            $table->renameColumn('nov_price', 'nov_amount');
            $table->renameColumn('dec_price', 'dec_amount');
        });

        Schema::table('actual_materials', function (Blueprint $table) {
            $table->decimal('jan_price', 15, 2)->default(0.00)->after('jan_qty');
            $table->decimal('feb_price', 15, 2)->default(0.00)->after('feb_qty');
            $table->decimal('mar_price', 15, 2)->default(0.00)->after('mar_qty');
            $table->decimal('apr_price', 15, 2)->default(0.00)->after('apr_qty');
            $table->decimal('may_price', 15, 2)->default(0.00)->after('may_qty');
            $table->decimal('jun_price', 15, 2)->default(0.00)->after('jun_qty');
            $table->decimal('jul_price', 15, 2)->default(0.00)->after('jul_qty');
            $table->decimal('aug_price', 15, 2)->default(0.00)->after('aug_qty');
            $table->decimal('sep_price', 15, 2)->default(0.00)->after('sep_qty');
            $table->decimal('oct_price', 15, 2)->default(0.00)->after('oct_qty');
            $table->decimal('nov_price', 15, 2)->default(0.00)->after('nov_qty');
            $table->decimal('dec_price', 15, 2)->default(0.00)->after('dec_qty');
        });

        DB::statement("UPDATE actual_materials SET period = '2025'");

        $months = [
            'jan',
            'feb',
            'mar',
            'apr',
            'may',
            'jun',
            'jul',
            'aug',
            'sep',
            'oct',
            'nov',
            'dec'
        ];

        foreach ($months as $month) {
            DB::statement("
            UPDATE actual_materials 
            SET {$month}_price = CASE
                WHEN {$month}_qty > 0 THEN ROUND({$month}_amount / {$month}_qty, 4)
                ELSE 0.00
            END
        ");
        }

        Schema::table('actual_materials', function (Blueprint $table) {
            $table->string('period', 4)->nullable(false)->change();
            $table->unique(['item_code', 'period']);
        });
    }

    public function down(): void
    {
        Schema::table('actual_materials', function (Blueprint $table) {
            $table->dropUnique(['item_code', 'period']);
            $table->dropColumn('id');
            $table->string('item_code')->primary()->change();
            $table->dropColumn('period');

            $table->dropColumn([
                'jan_price',
                'feb_price',
                'mar_price',
                'apr_price',
                'may_price',
                'jun_price',
                'jul_price',
                'aug_price',
                'sep_price',
                'oct_price',
                'nov_price',
                'dec_price',
            ]);
            $table->renameColumn('jan_amount', 'jan_price');
            $table->renameColumn('feb_amount', 'feb_price');
            $table->renameColumn('mar_amount', 'mar_price');
            $table->renameColumn('apr_amount', 'apr_price');
            $table->renameColumn('may_amount', 'may_price');
            $table->renameColumn('jun_amount', 'jun_price');
            $table->renameColumn('jul_amount', 'jul_price');
            $table->renameColumn('aug_amount', 'aug_price');
            $table->renameColumn('sep_amount', 'sep_price');
            $table->renameColumn('oct_amount', 'oct_price');
            $table->renameColumn('nov_amount', 'nov_price');
            $table->renameColumn('dec_amount', 'dec_price');
        });
    }
};
