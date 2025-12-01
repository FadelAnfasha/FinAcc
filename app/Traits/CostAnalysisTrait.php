<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait CostAnalysisTrait
{
    protected function getStandardCostHistoryPeriod($itemCode, $currentYear)
    {
        $history = DB::table('standard_cost')
            ->where('item_code', $itemCode)
            ->where('period', '<', $currentYear)
            ->orderBy('period', 'desc')
            ->first();

        return $history->period ?? null;
    }
}
