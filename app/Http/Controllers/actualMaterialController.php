<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActualMaterial;
use Illuminate\Support\Facades\Cache;

class actualMaterialController extends Controller
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
                if (count($row) >= 3) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
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

        // --- Langkah 5: Mengosongkan tabel Material sebelum impor ---
        actualMaterial::truncate();

        // --- Langkah 6: Validasi duplikasi dalam file ---
        foreach ($csvData as $row) {
            $itemCode = trim($row[1]); // Asumsi item_code di kolom pertama
            if (!empty($itemCode)) {
                $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
            }
        }

        // --- Langkah 7: Proses Impor dan Validasi Item Code ---
        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[1]);
            $price = trim($row[2]);

            // Regex untuk validasi format item_code
            // Kriteria:
            // 1. Terdiri dari 6 digit
            // 2. Dimulai dengan huruf 'F'
            // 3. Digit ke-2 & ke-3 adalah angka 15-24
            // 4. Digit ke-4 adalah huruf 'D', 'N', 'W', 'R', atau 'T'
            // 5. Digit ke-5 & ke-6 adalah angka 00-99 (running number)
            if (!preg_match('/^RF[DRS]\d{3}$/', $itemCode)) {
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
            actualMaterial::create([
                'item_code' => $itemCode,
                'price' => $price,
            ]);
            $addedItems[] = $itemCode;

            // --- Langkah 9: Memperbarui progres impor ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-acmat', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-progress-acmat', 100, now()->addMinutes(5));

        // --- Langkah 10: Mengalihkan (Redirect) dengan hasil impor ---
        return redirect()->route('bom.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function update(Request $request, $item_code)
    {
        // Validasi input
        $request->validate([
            // 'item_desc' => 'required',
            'in_stock' => 'required',
            'item_group' => 'required',
            'price' => 'required'
        ]);

        $in_stock = $request->input('in_stock');
        $price = $request->input('price');

        // Temukan material berdasarkan item_code dan perbarui
        $material = actualMaterial::findOrFail($item_code);

        $material->update([
            // 'item_desc' => $request->input('item_desc'),
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price, // Menggunakan nilai price yang sudah diolah
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


        // Generate item_code
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

        // dd($item_code, $in_stock, $price);
        // Create new material
        actualMaterial::create([
            'item_code' => $item_code,
            // 'item_desc' => $request->input('item_desc'),
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material added successfully with code : ' . $item_code);
    }
}
