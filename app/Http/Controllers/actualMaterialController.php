<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActualMaterial;
use Illuminate\Support\Facades\Cache;

class actualMaterialController extends Controller
{

    private function cleanValue($value)
    {
        $cleaned = trim($value);
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (float)$cleaned : 0;
    }

    private function getInvalidItemData($itemCode, $row, $reason)
    {
        return [
            'item_code' => $itemCode,
            'jan_price' => $row[2],
            'jan_qty' => $row[3],
            'feb_price' => $row[4],
            'feb_qty' => $row[5],
            'mar_price' => $row[6],
            'mar_qty' => $row[7],
            'apr_price' => $row[8],
            'apr_qty' => $row[9],
            'may_price' => $row[10],
            'may_qty' => $row[11],
            'jun_price' => $row[12],
            'jun_qty' => $row[13],
            'jul_price' => $row[14],
            'jul_qty' => $row[15],
            'aug_price' => $row[16],
            'aug_qty' => $row[17],
            'sep_price' => $row[18],
            'sep_qty' => $row[19],
            'oct_price' => $row[20],
            'oct_qty' => $row[21],
            'nov_price' => $row[22],
            'nov_qty' => $row[23],
            'dec_price' => $row[24],
            'dec_qty' => $row[25],
            'reason' => $reason
        ];
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

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

            $itemsToUpsert[] = [
                'item_code' => $itemCode,
                'jan_price' => $this->cleanValue($row[2]),
                'jan_qty' => $this->cleanValue($row[3]),
                'feb_price' => $this->cleanValue($row[4]),
                'feb_qty' => $this->cleanValue($row[5]),
                'mar_price' => $this->cleanValue($row[6]),
                'mar_qty' => $this->cleanValue($row[7]),
                'apr_price' => $this->cleanValue($row[8]),
                'apr_qty' => $this->cleanValue($row[9]),
                'may_price' => $this->cleanValue($row[10]),
                'may_qty' => $this->cleanValue($row[11]),
                'jun_price' => $this->cleanValue($row[12]),
                'jun_qty' => $this->cleanValue($row[13]),
                'jul_price' => $this->cleanValue($row[14]),
                'jul_qty' => $this->cleanValue($row[15]),
                'aug_price' => $this->cleanValue($row[16]),
                'aug_qty' => $this->cleanValue($row[17]),
                'sep_price' => $this->cleanValue($row[18]),
                'sep_qty' => $this->cleanValue($row[19]),
                'oct_price' => $this->cleanValue($row[20]),
                'oct_qty' => $this->cleanValue($row[21]),
                'nov_price' => $this->cleanValue($row[22]),
                'nov_qty' => $this->cleanValue($row[23]),
                'dec_price' => $this->cleanValue($row[24]),
                'dec_qty' => $this->cleanValue($row[25]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-actualMat', $progress, now()->addMinutes(5));
        }

        if (!empty($itemsToUpsert)) {
            actualMaterial::upsert(
                $itemsToUpsert,
                ['item_code'],
                $updateColumns
            );

            $addedItems = array_column($itemsToUpsert, 'item_code');
        }

        Cache::put('import-progress-actualMat', 100, now()->addMinutes(5));

        return redirect()->route('bom.masterActual')->with([
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
