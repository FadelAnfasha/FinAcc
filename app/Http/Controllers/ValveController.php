<?php

namespace App\Http\Controllers;

use App\Models\Valve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ValveController extends Controller
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
            // Ambil valve berdasarkan item_code
            $valve = Valve::where('item_code', $row[0])->first();

            // Persiapkan data baru untuk dibandingkan
            $newData = [
                'price' => is_numeric($row[1]) ? floatval($row[1]) : 0.0,
            ];

            // Jika valve belum ada, maka data baru akan ditambahkan
            if (!$valve) {
                $valve = Valve::create([
                    'item_code' => $row[0],
                    'price' => is_numeric($row[1]) ? floatval($row[1]) : 0.0,
                ]);
                $addedItems[] = $valve->item_code; // Tambahkan ke daftar addedItems
            } else {
                // Jika valve ada, periksa apakah ada perubahan pada data
                $isUpdated = false;

                // Bandingkan data lama dengan data baru
                foreach ($newData as $key => $value) {
                    if (round($valve->price, 2) !== round($newData['price'], 2)) {
                        $isUpdated = true;
                    }
                }

                // Jika ada perubahan, update data dan masukkan ke updatedItems
                if ($isUpdated) {
                    $valve->update($newData);
                    $updatedItems[] = $valve->item_code; // Tambahkan ke daftar updatedItems
                }
            }
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-valve', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-valve', 100, now()->addMinutes(5));

        redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
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
