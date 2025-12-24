<?php

namespace App\Http\Controllers;

use App\Models\StandardConsumable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpParser\PrettyPrinter\Standard;

class StandardConsumableController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $addedItems = [];
        $invalidItems = [];
        $codeCounts = [];

        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
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
            return redirect()->route('sc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('sc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        StandardConsumable::truncate();

        // --- Langkah 6: Validasi duplikasi dalam file ---
        foreach ($csvData as $row) {
            $itemCode = trim($row[0]); // Asumsi item_code di kolom pertama
            if (!empty($itemCode)) {
                $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
            }
        }

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
            StandardConsumable::create([
                'item_code' => $itemCode,
                'price' => $price,
            ]);
            $addedItems[] = $itemCode;

            // --- Langkah 9: Memperbarui progres impor ---
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-standard-consumable-progress', $progress, now()->addMinutes(5));
        }

        // Setelah loop selesai, set progres ke 100%.
        Cache::put('import-standard-consumable-progress', 100, now()->addMinutes(5));

        // --- Langkah 10: Mengalihkan (Redirect) dengan hasil impor ---
        return redirect()->route('sc.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function destroy($item_code)
    {
        $valve = StandardConsumable::findOrFail($item_code);
        $valve->delete();

        redirect()->route(route: 'sc.master');
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
        $valve = StandardConsumable::findOrFail($item_code);
        $valve->update([
            // 'item_desc' => $request->input('item_desc'),
            'price' => $price, // Menggunakan nilai price yang sudah diolah
        ]);

        redirect()->route(route: 'sc.master');
    }

    public function getPaginated(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');
        $itemCodeFilter = $request->input('item_code_filter');
        $descriptionFilter = $request->input('description_filter');
        $sortField = $request->input('sort_field', 'item_code');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = StandardConsumable::with([
            'bom' => function ($q) {
                $q->select('id', 'description', 'item_code');
            }
        ]);

        $joinNeeded = ($sortField === 'bom.description') || ($sortField === 'bom.item_code');

        if ($joinNeeded) {
            $query->leftJoin('standard_bill_of_materials as bom_table', 'standard_consumables.item_code', '=', 'bom_table.item_code');
        }

        $query->when($searchTerm, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('standard_consumables.item_code', 'LIKE', '%' . $term . '%')
                    ->orWhereHas('bom', function ($qBom) use ($term) {
                        $qBom->where('description', 'LIKE', '%' . $term . '%');
                    });
            });
        })
            ->when($itemCodeFilter, function ($query, $term) {
                $query->where('standard_consumables.item_code', 'LIKE', $term . '%');
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
            $query->orderBy('standard_consumables.' . $sortField, $sortOrder);
        }

        if ($joinNeeded) {
            $selects = ['standard_consumables.*'];
            $consumablePaginated = $query->select($selects)->paginate($perPage);
        } else {
            $consumablePaginated = $query->paginate($perPage);
        }

        return response()->json($consumablePaginated);
    }
}
