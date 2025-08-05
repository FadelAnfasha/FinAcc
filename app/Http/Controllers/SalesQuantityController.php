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
        // Array ini tidak digunakan untuk SalesQuantity, jadi bisa dihapus.
        // $codeCounts = [];

        // --- Langkah 3: Membaca File CSV ---
        $csvData = [];

        // Membuka file untuk dibaca dalam mode 'r' (read).
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            // Membaca baris pertama sebagai header dan mengabaikannya.
            $header = fgetcsv($handle, 1000, $delimiter);

            // Membaca setiap baris data dari file.
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Memastikan baris memiliki jumlah kolom yang memadai (minimal 4).
                if (count($row) >= 4) {
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

        // Mengosongkan tabel SalesQuantity sebelum memulai impor.
        SalesQuantity::truncate();

        // --- Langkah 5: Validasi dan Penyimpanan ke Database ---
        foreach ($csvData as $index => $row) {
            // Memperbaiki indeks kolom berdasarkan header CSV: Id;BP Code;Item Code;Sales Quantity
            $bpCode = trim($row[1]);
            $itemCode = trim($row[2]);
            $quantity = trim($row[3]);

            // Validasi: memastikan kuantitas adalah angka.
            // Memeriksa juga string kosong, yang tidak valid sebagai angka.
            if (!is_numeric($quantity)) {
                $invalidItems[] = ['id' => $row[0], 'bp_code' => $bpCode, 'item_code' => $itemCode, 'quantity' => $quantity, 'reason' => 'Quantity must be a numeric value.'];
                continue;
            }

            // Validasi: memastikan BP Code dan Item Code ada di master data.
            $bpExists = BusinessPartner::where('bp_code', $bpCode)->exists();
            $itemExists = CycleTime::where('item_code', $itemCode)->exists();

            if (!$bpExists) {
                $invalidItems[] = ['bp_code' => $bpCode, 'item_code' => $itemCode, 'quantity' => $quantity, 'reason' => 'BP Code not found in master data.'];
                continue;
            }

            if (!$itemExists) {
                $invalidItems[] = ['bp_code' => $bpCode, 'item_code' => $itemCode, 'quantity' => $quantity, 'reason' => 'Item Code not found in master data.'];
                continue;
            }

            // --- Langkah 6: Menyimpan Data Valid ke Database ---
            SalesQuantity::create([
                'id' => $row[0],
                'item_code' => $itemCode,
                'bp_code' => $bpCode,
                'quantity' => $quantity // Ubah nama kolom agar konsisten dengan model/database.
            ]);
            $addedItems[] = ['id' => $row[0], 'item_code' => $itemCode, 'bp_code' => $bpCode];

            // --- Langkah 7: Memperbarui Progres Impor (Opsional) ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-sq', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-progress-sq', 100, now()->addMinutes(5));

        // --- Langkah 8: Mengalihkan (Redirect) dengan Hasil Impor ---
        return redirect()->route('pc.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
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
