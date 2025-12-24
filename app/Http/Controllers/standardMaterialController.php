<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StandardMaterial;
use Illuminate\Support\Facades\Cache;


class StandardMaterialController extends Controller
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
                if (count($row) >= 19) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }
            fclose($handle);
        } else {
            return redirect()->route('sc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        // --- Langkah 4: Validasi Awal Jumlah Data ---
        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('sc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        // --- Langkah 5: Mengosongkan tabel Material sebelum impor ---
        StandardMaterial::truncate();

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
            $description = trim($row[2]);

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
                    'description' => $description,
                    'reason' => 'Invalid item code format.'
                ];
                continue;
            }

            // Pengecekan duplikasi dalam file
            if (($codeCounts[$itemCode] ?? 0) > 1) {
                $invalidItems[] = [
                    'item_code' => $itemCode,
                    'description' => $description,
                    'reason' => 'Duplicate in file.'
                ];
                // Lanjutkan ke baris berikutnya, jangan simpan duplikat
                continue;
            }

            // --- Langkah 8: Menyimpan data yang valid ke database ---
            StandardMaterial::create([
                'item_code' => $itemCode,
                'description' => $description,
                'in_stock' => $row[3],
                'item_group' => $row[4],
                'price' => $row[5]
            ]);
            $addedItems[] = $itemCode;

            // --- Langkah 9: Memperbarui progres impor ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-standard-material-progress', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-standard-material-progress', 100, now()->addMinutes(5));

        // --- Langkah 10: Mengalihkan (Redirect) dengan hasil impor ---
        return redirect()->route('sc.master')->with([
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
        $material = StandardMaterial::findOrFail($item_code);

        $material->update([
            // 'item_desc' => $request->input('item_desc'),
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price, // Menggunakan nilai price yang sudah diolah
        ]);

        redirect()->route(route: 'sc.master');
    }

    public function destroy($item_code)
    {
        $material = StandardMaterial::findOrFail($item_code);
        $material->delete();

        redirect()->route(route: 'sc.master');
    }

    public function getPaginated(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');
        $itemCodeFilter = $request->input('item_code_filter');
        $itemGroupFilter = $request->input('item_group_filter');
        $descriptionFilter = $request->input('description_filter');
        $sortField = $request->input('sort_field', 'item_code');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = StandardMaterial::with([
            'bom' => function ($q) {
                $q->select('id', 'description', 'item_code');
            }
        ]);

        $joinNeeded = ($sortField === 'bom.description') || ($sortField === 'bom.item_code');
        if ($joinNeeded) {
            $query->leftJoin('standard_bill_of_materials as bom_table', 'standard_materials.item_code', '=', 'bom_table.item_code');
        }

        $query->when($searchTerm, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('standard_materials.item_code', 'LIKE', '%' . $term . '%')
                    ->orWhere('standard_materials.item_group', 'LIKE', '%' . $term . '%')
                    ->orWhereHas('bom', function ($qBom) use ($term) {
                        $qBom->where('description', 'LIKE', '%' . $term . '%');
                    });
            });
        })
            ->when($itemCodeFilter, function ($query, $term) {
                $query->where('standard_materials.item_code', 'LIKE', '%' . $term . '%');
            })
            ->when($itemGroupFilter, function ($query, $term) {
                $query->where('standard_materials.item_group', 'LIKE', '%' . $term . '%');
            })
            ->when($descriptionFilter, function ($query, $term) {
                $query->whereHas('bom', function ($q) use ($term) {
                    $q->where('description', 'LIKE', '%' . $term . '%');
                });
            });

        if ($sortField === 'bom.description' || $sortField === 'bom.item_code') {
            $column = $sortField === 'bom.description' ? 'bom_table.description' : 'bom_table.item_code';
            $query->orderBy($column, $sortOrder);
        } else {
            $query->orderBy('standard_materials.' . $sortField, $sortOrder);
        }

        if ($joinNeeded) {
            $selects = ['standard_materials.*'];
            $materialPaginated = $query->select($selects)->paginate($perPage);
        } else {
            $materialPaginated = $query->paginate($perPage);
        }

        return response()->json($materialPaginated);
    }

    public function getAllMaterial(Request $request)
    {
        $material = StandardMaterial::all();
        return response()->json($material);
    }

    public function getGroupList(Request $request)
    {
        $itemGroup = StandardMaterial::distinct()->pluck('item_group');
        return response()->json($itemGroup);
    }
}
