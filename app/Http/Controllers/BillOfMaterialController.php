<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Models\BillOfMaterial;


class BillOfMaterialController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $addedItems = [];
        $invalidItems = [];
        $codeCounts = [];
        $nameCounts = [];

        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (count($row) >= 3) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }

            fclose($handle);
        } else {
            return redirect()->route('pc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('pc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        BillOfMaterial::truncate();

        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[0]);
            $description = trim($row[1]);

            $description = mb_convert_encoding($description, 'UTF-8', 'auto');
            $description = preg_replace('/[^a-zA-Z0-9\s.,()\-\_]/u', ' ', $description);
            $description = preg_replace('/\s+/', ' ', $description);

            BillOfMaterial::create([
                'item_code' => $itemCode,
                'description' => $description,
                'uom' => $row[2],
                'quantity' => $row[3],
                'warehouse' => $row[4],
                'depth' => $row[7],
                'bom_type' => $row[8]
            ]);
            $addedItems[] = $itemCode;

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-bom', $progress, now()->addMinutes(5));
        }

        Cache::put('import-progress-bom', 100, now()->addMinutes(5));

        return redirect()->route('bom.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function components($id)
    {
        $all = BillOfMaterial::orderBy('id')->get();
        $mainIndex = $all->search(fn($item) => $item->id == $id);

        if ($mainIndex === false) {
            return response()->json([]);
        }

        $components = collect();
        for ($i = $mainIndex + 1; $i < count($all); $i++) {
            if ($all[$i]->depth == 1) break;
            $components->push($all[$i]);
        }

        $finishGood = $all[$mainIndex];
        return redirect()->route('bom.master', ['component_id' => $id]);
    }
}
