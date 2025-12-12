<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\ActualMaterial;

class actualMaterialController extends Controller
{
    private function cleanValue($value)
    {
        $cleaned = trim($value);
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (float)$cleaned : 0;
    }

    private function calculateAveragePrice($amount, $quantity)
    {
        if ($quantity == 0) {
            return 0;
        }
        return $amount / $quantity;
    }

    private function getInvalidItemData($itemCode, $row, $reason)
    {
        return [
            'item_code' => $itemCode,
            'jan_amount' => $row[2],
            'jan_qty' => $row[3],
            'feb_amount' => $row[4],
            'feb_qty' => $row[5],
            'mar_amount' => $row[6],
            'mar_qty' => $row[7],
            'apr_amount' => $row[8],
            'apr_qty' => $row[9],
            'may_amount' => $row[10],
            'may_qty' => $row[11],
            'jun_amount' => $row[12],
            'jun_qty' => $row[13],
            'jul_amount' => $row[14],
            'jul_qty' => $row[15],
            'aug_amount' => $row[16],
            'aug_qty' => $row[17],
            'sep_amount' => $row[18],
            'sep_qty' => $row[19],
            'oct_amount' => $row[20],
            'oct_qty' => $row[21],
            'nov_amount' => $row[22],
            'nov_qty' => $row[23],
            'dec_amount' => $row[24],
            'dec_qty' => $row[25],
            'reason' => $reason
        ];
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'file' => 'required|mimes:csv,txt',
            'period' => 'required|integer|min:2000|max:' . (Carbon::now()->year + 1),
        ]);

        $importYear = $validatedData['period'];
        $file = $request->file('file');

        $itemsToUpsert = [];
        $addedItems = [];
        $invalidItems = [];
        $codeCounts = [];

        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (count($row) >= 26) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format. Expected 26 columns.)';
                }
            }
            fclose($handle);
        } else {
            return redirect()->route('bom.masterActual')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('bom.masterActual')->withErrors(['file' => 'The CSV file is empty.']);
        }

        foreach ($csvData as $row) {
            $itemCode = trim($row[1]);
            if (!empty($itemCode)) {
                $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
            }
        }

        $updateColumns = [
            'jan_amount',
            'jan_qty',
            'feb_amount',
            'feb_qty',
            'mar_amount',
            'mar_qty',
            'apr_amount',
            'apr_qty',
            'may_amount',
            'may_qty',
            'jun_amount',
            'jun_qty',
            'jul_amount',
            'jul_qty',
            'aug_amount',
            'aug_qty',
            'sep_amount',
            'sep_qty',
            'oct_amount',
            'oct_qty',
            'nov_amount',
            'nov_qty',
            'dec_amount',
            'dec_qty',
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
            'updated_at'
        ];

        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[1]);

            if (!preg_match('/^RF[DRS]\d{3}$/', $itemCode)) {
                $invalidItems[] = $this->getInvalidItemData($itemCode, $row, 'Invalid item code format.');
                continue;
            }

            if (($codeCounts[$itemCode] ?? 0) > 1) {
                $invalidItems[] = $this->getInvalidItemData($itemCode, $row, 'Duplicate in file.');
                continue;
            }

            $combinedData = [];
            $amountQtyData = [];
            $priceData = [];

            for ($i = 0; $i < 12; $i++) {
                $month = strtolower(date('M', mktime(0, 0, 0, $i + 1, 10)));
                $amountColumnIndex = 2 + ($i * 2);
                $qtyColumnIndex = 3 + ($i * 2);

                $amount = $this->cleanValue($row[$amountColumnIndex]);
                $quantity = $this->cleanValue($row[$qtyColumnIndex]);

                $avgPrice = $this->calculateAveragePrice($amount, $quantity);

                $amountQtyData[$month . '_amount'] = $amount;
                $amountQtyData[$month . '_qty'] = $quantity;
                $priceData[$month . '_price'] = round($avgPrice, 4);
            }

            $combinedData = array_merge($amountQtyData, $priceData);

            $itemsToUpsert[] = array_merge([
                'item_code' => $itemCode,
                'period' => $importYear,
                'created_at' => now(),
                'updated_at' => now(),
            ], $combinedData);

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-actualMat', $progress, now()->addMinutes(5));
        }

        if (!empty($itemsToUpsert)) {
            actualMaterial::upsert(
                $itemsToUpsert,
                ['item_code', 'period'],
                $updateColumns
            );

            $addedItems = array_column($itemsToUpsert, 'item_code');
        }

        Cache::put('import-progress-actualMat', 100, now()->addMinutes(5));

        return redirect()->route('ac.master')->with([
            'success' => 'CSV import process completed! ' . count($addedItems) . ' records processed.',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function update(Request $request, $item_code)
    {
        $request->validate([
            'in_stock' => 'required',
            'item_group' => 'required',
            'price' => 'required'
        ]);

        $in_stock = $request->input('in_stock');
        $price = $request->input('price');

        $material = actualMaterial::findOrFail($item_code);

        $material->update([
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price,
        ]);

        redirect()->route(route: 'pc.master');
    }

    public function destroy($item_code)
    {
        $material = actualMaterial::findOrFail($item_code);
        $material->delete();

        redirect()->route(route: 'pc.master');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            // 'item_desc' => 'required',
            'in_stock' => 'required',
            'item_group' => 'required',
            'price' => 'required',
        ]);


        $type = $request->input('type');
        $prefix = 'RF' . strtoupper(substr($type, 0, 1));
        $materials = actualMaterial::where('item_code', 'like', $prefix . '%')->orderBy('item_code', 'asc')->get();

        $existingNumbers = $materials->map(function ($material) use ($prefix) {
            return intval(substr($material->item_code, strlen($prefix)));
        })->toArray();

        $newNumber = '001';
        for ($i = 1; $i <= count($existingNumbers) + 1; $i++) {
            if (!in_array($i, $existingNumbers)) {
                $newNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
                break;
            }
        }

        $in_stock = str_replace(',', '', $request->input('in_stock'));
        $price = str_replace(',', '', $request->input('price'));

        $item_code = $prefix . $newNumber;

        actualMaterial::create([
            'item_code' => $item_code,
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material added successfully with code : ' . $item_code);
    }
}
