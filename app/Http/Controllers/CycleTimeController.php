<?php

namespace App\Http\Controllers;

use App\Models\CycleTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CycleTimeController extends Controller
{
    public function importSalesQuantity(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $addedItems = [];
        $invalidItems = [];
        $csvData = [];

        if (($handle = fopen($file->getRealPath(), 'r')) === FALSE) {
            return redirect()->route('pc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $delimiter = ';';
        // Lewati baris pertama (header)
        fgetcsv($handle, 1000, $delimiter);

        // Baca seluruh data CSV ke dalam array
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            // Asumsi: bp_code ada di kolom pertama (indeks 0) dan item_code di kolom kedua (indeks 1)
            if (count($row) >= 2) {
                $csvData[] = $row;
            } else {
                $invalidItems[] = [
                    'bp_code' => 'N/A',
                    'item_code' => 'N/A',
                    'reason' => 'Invalid row format.'
                ];
            }
        }

        fclose($handle);

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('pc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // --- Langkah Baru: Dapatkan semua kode yang ada di database untuk validasi cepat.
        $existingBpCodes = BusinessPartner::pluck('bp_code')->toArray();
        $existingItemCodes = CycleTime::pluck('item_code')->toArray();

        // --- Mendeteksi duplikasi dalam file CSV yang diunggah
        // Asumsi: Kombinasi bp_code dan item_code harus unik
        $uniqueKeys = [];
        $duplicateKeys = [];
        foreach ($csvData as $row) {
            $bpCode = trim($row[0]);
            $itemCode = trim($row[1]);
            $key = $bpCode . '-' . $itemCode;

            if (in_array($key, $uniqueKeys)) {
                $duplicateKeys[] = $key;
            } else {
                $uniqueKeys[] = $key;
            }
        }

        // Hapus semua data yang ada di tabel SalesQuantity.
        // Peringatan: Ini adalah aksi yang sangat merusak.
        SalesQuantity::truncate();

        foreach ($csvData as $index => $row) {
            $bpCode = trim($row[0]);
            $itemCode = trim($row[1]);

            // Cek duplikasi dalam file
            $key = $bpCode . '-' . $itemCode;
            if (in_array($key, $duplicateKeys)) {
                $invalidItems[] = [
                    'bp_code' => $bpCode,
                    'item_code' => $itemCode,
                    'reason' => 'Duplicate combination of BP Code and Item Code in file.'
                ];
                continue;
            }

            // Validasi: Cek keberadaan bp_code di database
            if (!in_array($bpCode, $existingBpCodes)) {
                $invalidItems[] = [
                    'bp_code' => $bpCode,
                    'item_code' => $itemCode,
                    'reason' => 'BP Code not found in Business Partner master data.'
                ];
                continue;
            }

            // Validasi: Cek keberadaan item_code di database
            if (!in_array($itemCode, $existingItemCodes)) {
                $invalidItems[] = [
                    'bp_code' => $bpCode,
                    'item_code' => $itemCode,
                    'reason' => 'Item Code not found in Cycle Time master data.'
                ];
                continue;
            }

            // Jika semua validasi lolos, buat data baru
            SalesQuantity::create([
                'bp_code' => $bpCode,
                'item_code' => $itemCode,
                'jan' => $row[2],
                'feb' => $row[3],
                'mar' => $row[4],
                'apr' => $row[5],
                'may' => $row[6],
                'jun' => $row[7],
                'jul' => $row[8],
                'aug' => $row[9],
                'sep' => $row[10],
                'oct' => $row[11],
                'nov' => $row[12],
                'dec' => $row[13],
            ]);

            $addedItems[] = "{$bpCode}-{$itemCode}";

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-sq', $progress, now()->addMinutes(5));
        }

        Cache::put('import-progress-sq', 100, now()->addMinutes(5));

        return redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function destroy($item_code)
    {
        $ct = CycleTime::findOrFail($item_code);
        $ct->delete();

        return redirect()->route('pc.master')->with('deleted', 'Cycle Time ' . $item_code . ' deleted successfully');
    }
}
