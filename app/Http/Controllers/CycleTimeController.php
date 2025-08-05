<?php

namespace App\Http\Controllers;

use App\Models\CycleTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CycleTimeController extends Controller
{
    public function import(Request $request)
    {
        // --- Langkah 1: Validasi File yang Diunggah ---
        // Memastikan file diunggah dan memiliki ekstensi .csv atau .txt.
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        // --- Langkah 2: Inisialisasi Variabel untuk Hasil Impor ---
        // Array untuk melacak item yang berhasil ditambahkan dan yang tidak valid.
        $addedItems = [];
        $invalidItems = [];
        // Array untuk menghitung duplikat item code di dalam file CSV itu sendiri.
        $codeCounts = [];

        // --- Langkah 3: Membaca File CSV ---
        $csvData = [];
        // Membuka file untuk dibaca dalam mode 'r' (read).
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            // Membaca baris pertama sebagai header dan mengabaikannya.
            $header = fgetcsv($handle, 1000, $delimiter);

            // Membaca setiap baris data dari file.
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Memastikan baris memiliki jumlah kolom yang memadai (minimal 43, dari index 0-42).
                if (count($row) >= 43) {
                    $csvData[] = $row;
                } else {
                    // Jika format baris tidak valid, tambahkan ke daftar invalid.
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }

            fclose($handle);
        } else {
            // Jika file gagal dibuka, arahkan kembali dengan pesan error.
            return redirect()->route('pc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        // --- Langkah 4: Validasi Awal Jumlah Data ---
        $total = count($csvData);
        if ($total === 0) {
            // Jika file kosong, arahkan kembali dengan pesan error.
            return redirect()->route('pc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // --- Langkah 5: Pass Pertama - Menghitung Duplikat Item Code di Dalam File ---
        // Mengosongkan tabel CycleTime sebelum memulai impor.
        CycleTime::truncate();

        foreach ($csvData as $row) {
            $itemCode = trim($row[0]);
            // Menambah hitungan untuk setiap Item Code.
            // Operator `??` memastikan nilai default 0 jika key belum ada.
            $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
        }

        // --- Langkah 6: Pass Kedua - Validasi dan Penyimpanan ke Database ---
        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[0]);
            $description = trim($row[2]); // Asumsi 'type' adalah deskripsi

            // Cek duplikat code yang ditemukan pada pass pertama.
            if (($codeCounts[$itemCode] ?? 0) > 1) {
                $invalidItems[] = ['item_code' => $itemCode, 'description' => $description, 'reason' => 'Duplicate in file.'];
                continue; // Lanjut ke baris berikutnya jika duplikat.
            }

            // Validasi format Item Code menggunakan regular expression.
            // Pola `^F(\d{2})([DMNRTW])(\d{2})$` berarti:
            // - Dimulai dengan 'F'
            // - Diikuti dua digit angka (dipetakan ke $matches[1])
            // - Diikuti satu huruf D, M, N, R, T, atau W (dipetakan ke $matches[2])
            // - Diikuti dua digit angka (dipetakan ke $matches[3])
            $isValidFormat = preg_match('/^F(\d{2})([DMNRTW])(\d{2})$/', $itemCode, $matches);
            $midDigits = isset($matches[1]) ? intval($matches[1]) : null;

            // Memeriksa validasi format dan rentang angka (15-24)
            if (!$isValidFormat || $midDigits < 15 || $midDigits > 24) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'description' => $description,
                    'reason' => 'Invalid code format or middle digits out of range (15-24).',
                ];
                continue;
            }

            // Validasi untuk memastikan semua kolom numeric adalah angka
            $numericColumns = array_slice($row, 3);
            $allNumeric = true;
            $allNumeric = true;
            foreach ($numericColumns as $value) {
                $trimmedValue = trim($value);
                // Hanya validasi jika nilainya tidak kosong.
                if ($trimmedValue !== '' && !is_numeric($trimmedValue)) {
                    $allNumeric = false;
                    break;
                }
            }

            if (!$allNumeric) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'description' => $description,
                    'reason' => 'One or more numeric fields are not valid numbers.',
                ];
                continue;
            }

            // --- Langkah 7: Menyimpan Data Valid ke Database ---
            CycleTime::create([
                'item_code' => $itemCode,
                'size' => $row[1],
                'type' => $row[2],
                'blanking' => $row[3],
                'blanking_eff' => $row[4],
                'spinDisc' => $row[5],
                'spinDisc_eff' => $row[6],
                'autoDisc' => $row[7],
                'autoDisc_eff' => $row[8],
                'manualDisc' => $row[9],
                'manualDisc_eff' => $row[10],
                'c3_sn' => $row[11],
                'c3_sn_eff' => $row[12],
                'repairC3' => $row[13],
                'repairC3_eff' => $row[14],
                'discLathe' => $row[15],
                'discLathe_eff' => $row[16],
                'rim1' => $row[17],
                'rim1_eff' => $row[18],
                'rim2' => $row[19],
                'rim2_eff' => $row[20],
                'rim2insp' => $row[21],
                'rim2insp_eff' => $row[22],
                'rim3' => $row[23],
                'rim3_eff' => $row[24],
                'coiler' => $row[25],
                'coiler_eff' => $row[26],
                'forming' => $row[27],
                'forming_eff' => $row[28],
                'assy1' => $row[29],
                'assy1_eff' => $row[30],
                'assy2' => $row[31],
                'assy2_eff' => $row[32],
                'machining' => $row[33],
                'machining_eff' => $row[34],
                'shotPeening' => $row[35],
                'shotPeening_eff' => $row[36],
                'ced' => $row[37],
                'ced_eff' => $row[38],
                'topcoat' => $row[39],
                'topcoat_eff' => $row[40],
                'packing_dom' => $row[41],
                'packing_exp' => $row[42],
            ]);
            $addedItems[] = $itemCode;

            // --- Langkah 8: Memperbarui Progres Impor (Opsional) ---
            // Menghitung persentase progres dan menyimpannya di cache.
            // Ini berguna untuk menampilkan status impor real-time di UI.
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-ct', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-progress-ct', 100, now()->addMinutes(5));

        // --- Langkah 9: Mengalihkan (Redirect) dengan Hasil Impor ---
        // Mengalihkan pengguna ke halaman master dan menyimpan hasil impor
        // (added, updated, invalid items) di sesi sebagai 'flash data'.
        // Flash data hanya tersedia untuk satu permintaan berikutnya.
        return redirect()->route('pc.master')->with([
            'success' => 'CSV import process completed!',
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
