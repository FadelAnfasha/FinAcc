<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public function up(): void
    {
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropUnique('diff_cost_unique');
            $table->string('period')->after('item_code')->nullable();
        });
        $monthNames = $this->monthNames;
        $costs = DB::table('difference_cost')->get();
        foreach ($costs as $cost) {

            $yearDigits = substr((string)$cost->actual_year, 0);
            $monthName = $monthNames[$cost->actual_month - 1];
            $newPeriod = '';
            if ($cost->actual_month == 1) {
                $newPeriod = "{$monthName}'{$yearDigits}";
            } else {
                $newPeriod = "YTM-{$monthName}'{$yearDigits}";
            }

            DB::table('difference_cost')
                ->where('id', $cost->id)
                ->update(['period' => $newPeriod]);
        }
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropColumn(['standard_year', 'standard_month', 'actual_year', 'actual_month']);
            $table->string('period')->nullable(false)->change();
            $table->unique(['item_code', 'period'], 'diff_cost_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropUnique('diff_cost_period_unique');

            $table->integer('standard_year')->after('item_code')->nullable();
            $table->integer('standard_month')->after('standard_year')->nullable();
            $table->integer('actual_year')->after('standard_month')->nullable();
            $table->integer('actual_month')->after('actual_year')->nullable();
        });

        $monthMap = array_flip($this->monthNames);
        $costs = DB::table('difference_cost')->get();

        foreach ($costs as $cost) {
            if (empty($cost->period)) {
                continue;
            }

            $period = $cost->period;
            $yearDigits = 0;
            $monthNumber = 0;

            $parts = explode("'", $period);
            if (count($parts) > 1) {
                $yearDigits = (int)$parts[1];
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
                $monthNumber = $monthIndex + 1;

                DB::table('difference_cost')
                    ->where('id', $cost->id)
                    ->update([
                        'standard_year' => $yearDigits,
                        'standard_month' => $monthNumber,
                        'actual_year' => $yearDigits,
                        'actual_month' => $monthNumber,
                    ]);
            }
        }

        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->integer('standard_year')->nullable(false)->change();
            $table->integer('standard_month')->nullable(false)->change();
            $table->integer('actual_year')->nullable(false)->change();
            $table->integer('actual_month')->nullable(false)->change();

            $table->unique(['item_code', 'standard_year', 'standard_month'], 'diff_cost_unique');
        });
    }
};
