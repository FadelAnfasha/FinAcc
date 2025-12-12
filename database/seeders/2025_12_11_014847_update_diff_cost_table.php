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
        // Pastikan skema sudah diupdate
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->integer('quantity')->after('period')->nullable();
            $table->string('qty_x_total_raw_material')->after('total')->nullable();
            $table->string('qty_x_total_process')->after('qty_x_total_raw_material')->nullable();
            $table->string('qty_x_total')->after('qty_x_total_process')->nullable();
        });

        // Ambil semua data Actual Sales Quantity dan kunci dengan item_code untuk pencarian cepat
        $actual_sales_quantities = ActualSalesQuantity::all()->keyBy('item_code');

        // Loop melalui setiap baris DifferenceCost (menggunakan chunk untuk efisiensi memori)
        DifferenceCost::chunk(500, function ($differenceCosts) use ($actual_sales_quantities) {

            foreach ($differenceCosts as $dc) {

                $periodString = $dc->period; // Contoh: "YTD-Feb'2025"

                // 1. Ekstraksi Bulan Singkatan (Misal: "Feb")
                $monthPart = str_replace('YTD-', '', $periodString);
                $monthKey = substr($monthPart, 0, 3); // Hasil: "Feb"

                // 2. Penentuan Nama Kolom Kuantitas (Misal: "feb_qty")
                $qtyColumn = strtolower($monthKey) . '_qty';

                // 3. Pencocokan dan Pengambilan Kuantitas
                $actualQty = $actual_sales_quantities->get($dc->item_code);
                $quantity = 0;

                if ($actualQty && isset($actualQty->{$qtyColumn})) {
                    // Konversi ke integer
                    $quantity = (int) $actualQty->{$qtyColumn};
                }

                // 4. Update Kolom
                $dc->quantity = $quantity;

                // Perhitungan perkalian (mengkonversi total cost ke float/numerik)
                $dc->qty_x_total_raw_material = $quantity * (float) $dc->total_raw_material;
                $dc->qty_x_total_process = $quantity * (float) $dc->total_process;
                $dc->qty_x_total = $quantity * (float) $dc->total;

                // 5. Update Period (Menambahkan "/Feb")
                $dc->period = $periodString . ' / ' . $monthKey;

                $dc->save();
            }
        });
    }

    public function down(): void
    {
        // 1. Mengembalikan Kolom 'period' ke nilai aslinya
        // Kunci: Ambil string yang berada sebelum pemisah ' / '. 
        // Ini adalah cara tercepat dan teraman untuk membersihkan sufiks " / Bulan".

        DB::table('difference_cost')->update([
            // SUBSTRING_INDEX(period, ' / ', 1) mengambil semua string sebelum kemunculan pertama ' / '
            // TRIM() menghilangkan spasi sisa di akhir.
            'period' => DB::raw("TRIM(SUBSTRING_INDEX(period, ' / ', 1))")
        ]);


        // 2. Menghapus kolom-kolom yang ditambahkan
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
