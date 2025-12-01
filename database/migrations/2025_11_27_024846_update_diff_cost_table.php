<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Traits\CostAnalysisTrait;

return new class extends Migration
{
    use CostAnalysisTrait;
    public function up(): void
    {
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->double('diff_disc')->default(0)->after('period');
            $table->double('diff_rim')->default(0)->after('diff_disc');
            $table->double('diff_sidering')->default(0)->after('diff_rim');
            $table->string('remark')->nullable()->after('total');
        });

        $diffCosts = DB::table('difference_cost')->get();

        foreach ($diffCosts as $diffItem) {
            $currentYear = substr($diffItem->period, -4);

            $sc = DB::table('standard_cost')
                ->where('item_code', $diffItem->item_code)
                ->where('period', $currentYear)
                ->first();

            $ac = DB::table('actual_cost')
                ->where('item_code', $diffItem->item_code)
                ->where('period', $diffItem->period)
                ->first();

            $sc_disc_price = $sc->disc_price ?? 0;
            $ac_disc_price = $ac->disc_price ?? 0;
            $sc_rim_price = $sc->rim_price ?? 0;
            $ac_rim_price = $ac->rim_price ?? 0;
            $sc_sidering_price = $sc->sidering_price ?? 0;
            $ac_sidering_price = $ac->sidering_price ?? 0;

            $diff_disc = $sc_disc_price - $ac_disc_price;
            $diff_rim = $sc_rim_price - $ac_rim_price;
            $diff_sidering = $sc_sidering_price - $ac_sidering_price;

            $componentMapping = [
                'disc' => ['std' => $sc_disc_price, 'act' => $ac_disc_price],
                'rim' => ['std' => $sc_rim_price, 'act' => $ac_rim_price],
                'sidering' => ['std' => $sc_sidering_price, 'act' => $ac_sidering_price],
            ];

            $previousSCPeriod = $this->getStandardCostHistoryPeriod($diffItem->item_code, $currentYear);

            $finalRemark = 'Normal';

            foreach ($componentMapping as $name => $data) {
                $std = $data['std'];
                $act = $data['act'];

                if ($std > 0 && $act == 0) {
                    $finalRemark = "No Budget";
                    break;
                }
            }

            if (str_starts_with($finalRemark, 'Normal')) {
                foreach ($componentMapping as $name => $data) {
                    $std = $data['std'];
                    $act = $data['act'];

                    if ($std == 0 && $act > 0) {
                        if ($previousSCPeriod) {
                            $finalRemark = "Using previous standard cost {$previousSCPeriod}";
                        } else {
                            $finalRemark = "New Product";
                        }
                        break;
                    }
                }
            }

            DB::table('difference_cost')
                ->where('id', $diffItem->id)
                ->update([
                    'diff_disc' => $diff_disc,
                    'diff_rim' => $diff_rim,
                    'diff_sidering' => $diff_sidering,
                    'remark' => $finalRemark,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('difference_cost', function (Blueprint $table) {
            $table->dropColumn(['diff_disc', 'diff_rim', 'diff_sidering', 'remark']);
        });
    }
};
