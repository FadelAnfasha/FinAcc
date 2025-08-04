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
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $addedItems = [];
        $updatedItems = [];
        $invalidItems = [];
        $csvData = [];

        // Membaca file CSV dengan delimiter ';'
        if (($handle = fopen($file->getRealPath(), 'r')) === FALSE) {
            return redirect()->route('pc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $delimiter = ';';
        // Lewati baris pertama (header)
        fgetcsv($handle, 1000, $delimiter);

        // Baca seluruh data CSV ke dalam array
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $csvData[] = $row;
        }

        fclose($handle);

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('pc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // Hapus semua data yang ada sebelum mengimpor yang baru
        SalesQuantity::truncate();

        foreach ($csvData as $index => $row) {
            // Asumsi: kolom 0=id, 1=bp_code, 2=item_code, 3=quantity
            if (count($row) < 4) {
                $invalidItems[] = ['bp_code' => 'N/A', 'item_code' => 'N/A', 'reason' => 'Invalid row format.'];
                continue;
            }

            $id = trim($row[0]);
            $bp_code = trim($row[1]);
            $item_code = trim($row[2]);
            $quantity = trim($row[3]);

            // Validasi: Pastikan quantity adalah angka
            if (!is_numeric($quantity)) {
                $invalidItems[] = ['bp_code' => $bp_code, 'item_code' => $item_code, 'reason' => 'Quantity must be a numeric value.'];
                continue;
            }

            // Validasi keberadaan BP dan Item Code di database
            $bpExists = BusinessPartner::where('bp_code', $bp_code)->exists();
            $itemExists = CycleTime::where('item_code', $item_code)->exists();

            if (!$bpExists) {
                $invalidItems[] = ['bp_code' => $bp_code, 'item_code' => $item_code, 'reason' => 'BP Code not found in master data.'];
                continue;
            }

            if (!$itemExists) {
                $invalidItems[] = ['bp_code' => $bp_code, 'item_code' => $item_code, 'reason' => 'Item Code not found in master data.'];
                continue;
            }

            $newData = [
                'id' => $id,
                'bp_code' => $bp_code,
                'item_code' => $item_code,
                'quantity' => intval($quantity),
            ];

            // Karena menggunakan truncate, kita hanya perlu create
            SalesQuantity::create($newData);
            $addedItems[] = "{$bp_code} - {$item_code}";

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-sq', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-sq', 100, now()->addMinutes(5));

        return redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully.',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
            'invalidItems' => $invalidItems
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
