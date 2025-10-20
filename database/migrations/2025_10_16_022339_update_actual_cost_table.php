<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Pastikan ini ada
return new class extends Migration
{

    private $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public function up(): void
    {
        Schema::table('actual_cost', function (Blueprint $table) {
            // 1. Hapus Kunci Unik lama (sebelum drop kolom)
            $table->dropUnique(['item_code', 'report_year', 'report_month']);

            // 2. Tambahkan kolom 'period' BARU yang mengizinkan NULL sementara
            $table->string('period')->after('item_code')->nullable();
        });

        $monthNames = $this->monthNames;

        $costs = DB::table('actual_cost')->get();

        foreach ($costs as $cost) {

            $yearDigits = substr((string)$cost->report_year, 0);
            $monthName = $monthNames[$cost->report_month - 1];
            $newPeriod = '';
            if ($cost->report_month == 1) {
                $newPeriod = "{$monthName}'{$yearDigits}";
            } else {
                $newPeriod = "YTM-{$monthName}'{$yearDigits}";
            }

            // Update baris di database
            DB::table('actual_cost')
                ->where('id', $cost->id)
                ->update(['period' => $newPeriod]);
        }

        Schema::table('actual_cost', function (Blueprint $table) {
            $table->dropColumn(['report_year', 'report_month']);
            $table->string('period')->nullable(false)->change(); // Jadikan NOT NULL
            $table->unique(['item_code', 'period'], 'actual_cost_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('actual_cost', function (Blueprint $table) {
            $table->dropUnique('actual_cost_period_unique');

            $table->integer('report_year')->after('item_code')->nullable();
            $table->integer('report_month')->after('report_year')->nullable();
        });



        $monthMap = array_flip($this->monthNames);
        $costs = DB::table('actual_cost')->get();

        foreach ($costs as $cost) {
            if (empty($cost->period)) {
                continue;
            }

            $period = $cost->period;
            $yearDigits = 0;
            $monthNumber = 0;


            $parts = explode("'", $period);
            if (count($parts) > 1) {
                $yearDigits = 2000 + (int)$parts[1];
            }

            $monthPart = $parts[0];
            $endMonthName = '';

            if (str_contains($monthPart, '-')) {
                $monthParts = explode('-', $monthPart);
                $endMonthName = $monthParts[1];
            } else {
                $endMonthName = $monthPart;
            }
            $monthIndex = $monthMap[$endMonthName] ?? -1;

            if ($yearDigits > 0 && $monthIndex !== -1) {
                $monthNumber = $monthIndex + 1; // Konversi 0-11 menjadi 1-12

                DB::table('actual_cost')
                    ->where('id', $cost->id)
                    ->update([
                        'report_year' => $yearDigits,
                        'report_month' => $monthNumber,
                    ]);
            }
        }


        Schema::table('actual_cost', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->integer('report_year')->nullable(false)->change();
            $table->integer('report_month')->nullable(false)->change();
            $table->unique(['item_code', 'report_year', 'report_month']);
        });
    }
};
