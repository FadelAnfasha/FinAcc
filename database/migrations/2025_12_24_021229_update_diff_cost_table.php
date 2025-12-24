<?php

use App\Models\ActualSalesQuantity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DifferenceCost;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->integer('quantity')->after('period')->nullable();
            $table->string('qty_x_total_raw_material')->after('total')->nullable();
            $table->string('qty_x_total_process')->after('qty_x_total_raw_material')->nullable();
            $table->string('qty_x_total')->after('qty_x_total_process')->nullable();
        });

        $actual_sales_quantities = ActualSalesQuantity::all()->keyBy('item_code');

        DifferenceCost::chunk(500, function ($differenceCosts) use ($actual_sales_quantities) {

            foreach ($differenceCosts as $dc) {

                $periodString = $dc->period;

                $monthPart = str_replace('YTD-', '', $periodString);
                $monthKey = substr($monthPart, 0, 3); // Hasil: "Feb"

                $qtyColumn = strtolower($monthKey) . '_qty';

                $actualQty = $actual_sales_quantities->get($dc->item_code);
                $quantity = 0;

                if ($actualQty && isset($actualQty->{$qtyColumn})) {
                    $quantity = (int) $actualQty->{$qtyColumn};
                }

                $dc->quantity = $quantity;

                $dc->qty_x_total_raw_material = $quantity * (float) $dc->total_raw_material;
                $dc->qty_x_total_process = $quantity * (float) $dc->total_process;
                $dc->qty_x_total = $quantity * (float) $dc->total;

                $dc->period = $periodString . ' / ' . $monthKey;

                $dc->save();
            }
        });
    }

    public function down(): void
    {

        DB::table('difference_cost')->update([
            'period' => DB::raw("TRIM(SUBSTRING_INDEX(period, ' / ', 1))")
        ]);

        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'qty_x_total_raw_material',
                'qty_x_total_process',
                'qty_x_total'
            ]);
        });
    }
};
