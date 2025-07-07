<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use Illuminate\Http\Request;
use Symfony\Component\String\TruncateMode;
use Illuminate\Support\Facades\Cache;

class BillOfMaterialController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file));

        // Lewati baris pertama (header)
        $header = array_shift($csvData);

        $addedItems = [];
        $updatedItems = [];
        $invalidItems = [];
        BillOfMaterial::truncate();
        $validGroup = session('validGroup', false);
        $total = count($csvData);

        foreach ($csvData as $index => $data) {
            // Ambil no urut dari index 0
            $order = intval($data[0] ?? 0); // <= nomor urut dari frontend

            $item_code = $data[0] ?? null;
            $description = $data[1] ?? null;
            $uom = $data[2] ?? '-';
            $quantity = is_numeric($data[3]) ? floatval($data[3]) : 0.0;
            $warehouse = $data[4] ?? null;
            $depth = isset($data[5]) ? intval($data[5]) : null;
            $bom_type = $data[6] ?? null;

            if ($depth === 1) {
                $validGroup = false;
                $isValid = preg_match('/^F(\d{2})([DMNRTW])(\d{2})$/', $item_code, $matches);
                $midDigits = isset($matches[1]) ? intval($matches[1]) : null;

                if (!$isValid || $midDigits < 15 || $midDigits > 24) {
                    session(['validGroup' => $validGroup]);
                    $invalidItems[] = $item_code;
                    continue;
                }

                $validGroup = true;
                session(['validGroup' => $validGroup]);
            }

            if (($depth > 1 && $validGroup) || ($depth === 1 && $validGroup)) {
                BillOfMaterial::create([
                    'item_code' => $item_code,
                    'description' => $description,
                    'uom' => $uom,
                    'quantity' => $quantity,
                    'warehouse' => $warehouse,
                    'depth' => $depth,
                    'bom_type' => $bom_type,
                    'created_at' => now()->addMicroseconds($order), // <- supaya tetap urut
                ]);
                $addedItems[] = $item_code;
            }
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-bom', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-bom', 100, now()->addMinutes(5));

        return redirect()->route('bom.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
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
