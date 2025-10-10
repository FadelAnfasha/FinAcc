<?php

namespace App\Http\Controllers;

use App\Models\ActualSalesQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class actualSalesQuantityController extends Controller
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
        // Variabel $nameCounts tidak digunakan, jadi dihapus

        // --- Langkah 3: Membaca File CSV ---
        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            // Lewati header
            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Asumsi file CSV memiliki 2 kolom data: 'item_code' dan 'description'
                if (count($row) >= 12) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }
            fclose($handle);
        } else {
            return redirect()->route('bom.masterActual')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        // --- Langkah 4: Validasi Awal Jumlah Data ---
        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('bom.masterActual')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // --- Langkah 5: Mengosongkan tabel Material sebelum impor ---
        ActualSalesQuantity::truncate();

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

            // Regex untuk validasi format item_code
            // Kriteria:
            // 1. Terdiri dari 6 digit
            // 2. Dimulai dengan huruf 'F'
            // 3. Digit ke-2 & ke-3 adalah angka 15-24
            // 4. Digit ke-4 adalah huruf 'D', 'N', 'W', 'R', atau 'T'
            // 5. Digit ke-5 & ke-6 adalah angka 00-99 (running number)
            if (!preg_match('/^F(1[5-9]|2[0-4])[DNWRT]\d{2}$/', $itemCode)) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'reason' => 'Invalid item code format.'
                ];
                continue;
            }

            // Pengecekan duplikasi dalam file
            if (($codeCounts[$itemCode] ?? 0) > 1) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'reason' => 'Duplicate in file.'
                ];
                // Lanjutkan ke baris berikutnya, jangan simpan duplikat
                continue;
            }
            // --- Langkah 8: Menyimpan data yang valid ke database ---
            ActualSalesQuantity::create([
                'item_code' => $itemCode,
                'jan_qty' => $row[1],
                'feb_qty' => $row[2],
                'mar_qty' => $row[3],
                'apr_qty' => $row[4],
                'may_qty' => $row[5],
                'jun_qty' => $row[6],
                'jul_qty' => $row[7],
                'aug_qty' => $row[8],
                'sep_qty' => $row[9],
                'oct_qty' => $row[10],
                'nov_qty' => $row[11],
                'dec_qty' => $row[12],

            ]);
            $addedItems[] = $itemCode;
            // --- Langkah 9: Memperbarui progres impor ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-acsqty', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-progress-acsqty', 100, now()->addMinutes(5));

        // --- Langkah 10: Mengalihkan (Redirect) dengan hasil impor ---
        return redirect()->route('bom.masterActual')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }
}
