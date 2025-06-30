<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
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

        foreach ($csvData as $row) {
            // Ambil material berdasarkan item_code
            $material = Material::where('item_code', $row[0])->first();

            // Persiapkan data baru untuk dibandingkan
            $newData = [
                'in_stock' => is_numeric($row[1]) ? intval($row[1]) : 0,
                'item_group' => $row[2],
                'price' => is_numeric($row[3]) ? floatval($row[3]) : 0.0,
            ];

            // dd($material, $row[0], $newData);

            // Jika material belum ada, maka data baru akan ditambahkan
            if (!$material) {
                $material = Material::create([
                    'item_code' => $row[0],
                    'in_stock' => is_numeric($row[1]) ? intval($row[1]) : 0,
                    'item_group' => $row[2],
                    'price' => is_numeric($row[3]) ? floatval($row[3]) : 0.0,
                ]);
                $addedItems[] = $material->item_code; // Tambahkan ke daftar addedItems
            } else {
                // Jika material ada, periksa apakah ada perubahan pada data
                $isUpdated = false;

                // Bandingkan data lama dengan data baru
                foreach ($newData as $key => $value) {
                    if ($material->{$key} != $value) {
                        $isUpdated = true;
                        break;
                    }
                }

                // Jika ada perubahan, update data dan masukkan ke updatedItems
                if ($isUpdated) {
                    $material->update($newData);
                    $updatedItems[] = $material->item_code; // Tambahkan ke daftar updatedItems
                }
            }
        }

        // Kirim data feedback ke view
        redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
        ]);
    }
}
