<?php

namespace App\Services;

use App\Models\RawSalesQty;
use App\Models\SalesQuantity;
use App\Models\WagesDist;
use App\Models\WagesDistribution;

class ReportService
{
    protected array $processes = [
        'blanking',
        'spinDisc',
        'autoDisc',
        'manualDisc',
        'discLathe',
        'Total Disc',

        'rim1',
        'rim2',
        'rim3',
        'Total Rim',

        'coiler',
        'forming',
        'Total Sidering',

        'assy1',
        'assy2',
        'machining',
        'shotPeening',
        'Total Assy',

        'ced',
        'topcoat',
        'Total Painting',

        'packing_dom',
        'packing_exp',
        'Total Packaging'

    ];

    protected array $groupings = [
        'Total Disc'      => ['blanking', 'spinDisc', 'autoDisc', 'manualDisc', 'discLathe'],
        'Total Rim'       => ['rim1', 'rim2', 'rim3'],
        'Total Sidering'  => ['coiler', 'forming'],
        'Total Assy'      => ['assy1', 'assy2', 'machining', 'shotPeening'],
        'Total Painting'  => ['ced', 'topcoat'],
        'Total Packaging' => ['packing_dom', 'packing_exp'],
        'Total'        => ['Total Disc', 'Total Rim', 'Total Sidering', 'Total Assy', 'Total Painting', 'Total Packaging']
    ];

    protected array $line = [
        'Max of Disc',
        'Max of Rim',
        'Max of Sidering',
        'Max of Assy',
        'Max of CED',
        'Max of Topcoat',
        'Max of Packaging',
        'Max of Total'
    ];

    public function calculateCTxSQ()
    {
        $datas = SalesQuantity::with(['bp', 'item'])->get();

        return $datas->map(function ($data) {
            $calc = [];

            foreach ($this->processes as $proc) {
                $field = str_replace(['/', ' '], '', $proc);
                $value = optional($data->item)->{$field} * $data->quantity;
                $calc[$proc] = ceil($value * 100) / 100; // dibulatkan ke atas 2 angka desimal
            }


            // Hitung total dari grup proses
            foreach ($this->groupings as $group => $members) {
                $calc[$group] = array_sum(array_map(fn($p) => $calc[$p] ?? 0, $members));
            }

            return (object)[
                'bp_code' => $data->bp_code,
                'bp_name' => optional($data->bp)->bp_name,
                'item_code' => $data->item_code,
                'type' => optional($data->item)->type,
                'quantity' => $data->quantity,
                'ctxsq' => $calc,
            ];
        });
    }

    public function calculateBaseCost($ctxsqData)
    {
        $wages = WagesDistribution::first();
        $totals = [];
        foreach ($this->processes as $proc) {
            $totals[$proc] = $ctxsqData->sum(fn($item) => $item->ctxsq[$proc] ?? 0);
        }

        return $ctxsqData->map(function ($item) use ($totals, $wages) {
            $base = [];

            foreach ($this->processes as $proc) {
                $value = $item->ctxsq[$proc] ?? 0;
                $total = $totals[$proc] ?: 1;
                $weight = $wages->{$proc} ?? 0;
                $raw = ($value / $total) * $weight;
                $base[$proc] = ceil($raw * 100) / 100;
                // dump($proc,$value, $total, $weight, $raw, $base);
            }


            foreach ($this->groupings as $group => $members) {
                $groupSum = array_sum(array_map(fn($p) => $base[$p] ?? 0, $members));
                $base[$group] = ceil($groupSum * 100) / 100;
            }
            return (object)[
                'bp_code' => $item->bp_code,
                'bp_name' => $item->bp_name,
                'item_code' => $item->item_code,
                'type' => $item->type,
                'quantity' => $item->quantity,
                'basecost' => $base,
            ];
        });
    }

    public function calculateCostPerProcess($baseCostData)
    {
        return $baseCostData->map(function ($item) {
            $cpp = [];

            foreach (array_keys($item->basecost) as $proc) {
                $cpp[$proc] = $item->quantity > 0 ? ($item->basecost[$proc] ?? 0) / $item->quantity : 0;
            }

            return (object)[
                'bp_code' => $item->bp_code,
                'bp_name' => $item->bp_name,
                'item_code' => $item->item_code,
                'type' => $item->type,
                'quantity' => $item->quantity,
                'cpp' => $cpp,
            ];
        });
    }

    public function calculateProcessCost($cppData)
    {
        return $cppData->map(function ($item) {
            $grouped = [];

            foreach ($this->line as $lineGroup) {
                // Contoh: "Max of CED" => "CED", lalu jadi "ced"
                $groupName = trim(str_replace('Max of ', '', $lineGroup));
                $groupKey = $groupName === 'Total' ? 'Total' : 'Total ' . $groupName;

                // Ambil dari grouping kalau ada
                if (isset($this->groupings[$groupKey])) {
                    $members = $this->groupings[$groupKey];
                } else {
                    // Kalau tidak ada di $groupings, anggap ini proses individual
                    $members = [strtolower($groupName)];
                }

                // Jumlahkan cost per process dari semua anggota
                $grouped[$lineGroup] = array_sum(array_map(fn($proc) => $item->cpp[$proc] ?? 0, $members));
            }

            return (object)[
                'bp_code' => $item->bp_code,
                'bp_name' => $item->bp_name,
                'item_code' => $item->item_code,
                'type' => $item->type,
                'quantity' => $item->quantity,
                'process_cost' => $grouped,
            ];
        });
    }

    public function getProcessList(): array
    {
        return array_unique(array_merge(
            $this->processes,
            array_keys($this->groupings)
        ));
    }

    public function getLine(): array
    {
        return $this->line;
    }
}
