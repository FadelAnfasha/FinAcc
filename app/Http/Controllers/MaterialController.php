<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $total = count($csvData);

        foreach ($csvData as $index => $row) {
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
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-mat', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-mat', 100, now()->addMinutes(5));


        // Kirim data feedback ke view
        redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
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
        $material = Material::findOrFail($item_code);

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
        $material = Material::findOrFail($item_code);
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
        $materials = Material::where('item_code', 'like', $prefix . '%')->orderBy('item_code', 'asc')->get();

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
        Material::create([
            'item_code' => $item_code,
            // 'item_desc' => $request->input('item_desc'),
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material added successfully with code : ' . $item_code);
    }
}
