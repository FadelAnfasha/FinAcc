<?php

namespace App\Http\Controllers;

use App\Models\SalesQuantity;
use Illuminate\Http\Request;
use App\Models\BusinessPartner;
use App\Models\CycleTime;
use Illuminate\Support\Facades\Cache;

class SalesQuantityController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file));

        $header = array_shift($csvData); // Skip header

        $addedItems = [];
        $updatedItems = [];
        $skippedItems = [];

        $total = count($csvData);

        foreach ($csvData as $index => $row) {
            $id = trim($row[0]);
            $bp_code = trim($row[1]);
            $item_code = trim($row[2]);
            $quantity = is_numeric($row[3]) ? intval($row[3]) : 0;

            // Validasi keberadaan BP dan Item Code
            $bpExists = BusinessPartner::where('bp_code', $bp_code)->exists();
            $itemExists = CycleTime::where('item_code', $item_code)->exists();

            if (!$bpExists) {
                $skippedItems[] = "{$bp_code} - {$item_code} (BP code not found)";
                continue;
            }

            if (!$itemExists) {
                $skippedItems[] = "{$bp_code} - {$item_code} (Item code not found)";
                continue;
            }

            // Cek apakah data berdasarkan ID sudah ada
            $salesQty = SalesQuantity::find($id);

            $newData = [
                'bp_code' => $bp_code,
                'item_code' => $item_code,
                'quantity' => $quantity,
            ];

            if (!$salesQty) {
                // Data belum ada, tambahkan
                SalesQuantity::create(array_merge(['id' => $id], $newData));
                $addedItems[] = "{$bp_code} - {$item_code}";
            } else {
                // Data sudah ada, cek apakah perlu update
                $isUpdated = false;
                foreach ($newData as $key => $value) {
                    if ($salesQty->{$key} != $value) {
                        $isUpdated = true;
                        break;
                    }
                }

                if ($isUpdated) {
                    $salesQty->update($newData);
                    $updatedItems[] = "{$bp_code} - {$item_code}";
                }
            }
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-sq', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-sq', 100, now()->addMinutes(5));


        return redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully.',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
            'skippedItems' => $skippedItems
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);

        $salesqty = SalesQuantity::findOrFail($id);

        $salesqty->update([
            'quantity' => $request->quantity,
        ]);

        return redirect()->route('pc.master')->with([
            'success' => 'Data updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $sq = SalesQuantity::findOrFail($id);
        $sq->delete();

        return redirect()->route('pc.master');
    }
}
