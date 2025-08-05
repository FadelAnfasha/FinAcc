<?php

namespace App\Http\Controllers;

use App\Models\Valve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ValveController extends Controller
{
    public function import(Request $request)
    {
        // --- Langkah 1: Validasi File yang Diunggah ---
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        // --- Langkah 2: Inisialisasi Variabel untuk Hasil Impor ---
        $addedItems = [];
        $invalidItems = [];
        $codeCounts = [];

        // --- Langkah 3: Membaca File CSV ---
        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            // Lewati header
            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Asumsi file CSV memiliki 2 kolom data: 'item_code' dan 'price'
                if (count($row) >= 2) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = [
                        'item_code' => isset($row[0]) ? trim($row[0]) : null,
                        'price' => isset($row[1]) ? trim($row[1]) : null,
                        'reason' => 'Invalid row format'
                    ];
                }
            }
            fclose($handle);
        } else {
            return redirect()->route('bom.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        // --- Langkah 4: Validasi Awal Jumlah Data ---
        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('bom.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // --- Langkah 5: Mengosongkan tabel Valve sebelum impor ---
        Valve::truncate();

        // --- Langkah 6: Validasi duplikasi dalam file ---
        foreach ($csvData as $row) {
            $itemCode = trim($row[0]); // Asumsi item_code di kolom pertama
            if (!empty($itemCode)) {
                $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
            }
        }

        // --- Langkah 7: Proses Impor dan Validasi Item Code ---
        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[0]);
            $price = trim($row[1]);

            if (!is_numeric($price)) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'price' => $price,
                    'reason' => 'Price must be number.'
                ];
                continue;
            }

            // Regex untuk validasi format item_code valve:
            // Kriteria:
            // 1. Terdiri dari 6 digit
            // 2. Dimulai dengan "CGP"
            // 3. Tiga digit terakhir adalah angka (000-999)
            if (!preg_match('/^CGP\d{3}$/', $itemCode)) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'price' => $price,
                    'reason' => 'Invalid item code format.'
                ];
                continue;
            }

            // Pengecekan duplikasi dalam file
            if (($codeCounts[$itemCode] ?? 0) > 1) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'price' => $price,
                    'reason' => 'Duplicate in file.'
                ];
                // Lanjutkan ke baris berikutnya, jangan simpan duplikat
                continue;
            }

            // --- Langkah 8: Menyimpan data yang valid ke database ---
            Valve::create([
                'item_code' => $itemCode,
                'price' => $price,
            ]);
            $addedItems[] = $itemCode;

            // --- Langkah 9: Memperbarui progres impor ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-valve', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-progress-valve', 100, now()->addMinutes(5));

        // --- Langkah 10: Mengalihkan (Redirect) dengan hasil impor ---
        return redirect()->route('bom.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function destroy($item_code)
    {
        $valve = Valve::findOrFail($item_code);
        $valve->delete();

        redirect()->route(route: 'pc.master');
    }

    public function update(Request $request, $item_code)
    {
        // Validasi input
        $request->validate([
            'price' => 'required'
        ]);

        // Menghapus pemisah ribuan (titik) pada price
        $price = $request->input('price');

        // Temukan valve berdasarkan item_code dan perbarui
        $valve = Valve::findOrFail($item_code);
        $valve->update([
            // 'item_desc' => $request->input('item_desc'),
            'price' => $price, // Menggunakan nilai price yang sudah diolah
        ]);

        redirect()->route(route: 'pc.master');
    }
}
