<?php

namespace App\Http\Controllers;

use App\Models\Packing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PackingController extends Controller
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
        $total = count($csvData);

        foreach ($csvData as $index => $row) {
            // Ambil packing berdasarkan item_code
            $packing = Packing::where('item_code', $row[0])->first();

            // Persiapkan data baru untuk dibandingkan
            $newData = [
                'price' => is_numeric($row[1]) ? floatval($row[1]) : 0.0,
            ];

            // Jika packing belum ada, maka data baru akan ditambahkan
            if (!$packing) {
                $packing = Packing::create([
                    'item_code' => $row[0],
                    'price' => is_numeric($row[1]) ? floatval($row[1]) : 0.0,
                ]);
                $addedItems[] = $packing->item_code; // Tambahkan ke daftar addedItems
            } else {
                // Jika packing ada, periksa apakah ada perubahan pada data
                $isUpdated = false;

                // Bandingkan data lama dengan data baru
                foreach ($newData as $key => $value) {
                    if (round($packing->price, 2) !== round($newData['price'], 2)) {
                        $isUpdated = true;
                    }
                }

                // Jika ada perubahan, update data dan masukkan ke updatedItems
                if ($isUpdated) {
                    $packing->update($newData);
                    $updatedItems[] = $packing->item_code; // Tambahkan ke daftar updatedItems
                }
            }
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-pack', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-pack', 100, now()->addMinutes(5));

        redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
        ]);
    }

    public function destroy($item_code)
    {
        $packing = Packing::findOrFail($item_code);
        $packing->delete();

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

        // Temukan packing berdasarkan item_code dan perbarui
        $packing = Packing::findOrFail($item_code);
        $packing->update([
            // 'item_desc' => $request->input('item_desc'),
            'price' => $price, // Menggunakan nilai price yang sudah diolah
        ]);

        redirect()->route(route: 'pc.master');
    }
}
