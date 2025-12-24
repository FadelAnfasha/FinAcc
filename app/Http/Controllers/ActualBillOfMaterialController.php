<?php

namespace App\Http\Controllers;

use App\Models\ActualBillOfMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class ActualBillOfMaterialController extends Controller
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
        $nameCounts = [];

        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (count($row) >= 3) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }

            fclose($handle);
        } else {
            return redirect()->route('ac.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('ac.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        ActualBillOfMaterial::truncate();

        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[0]);
            $description = trim($row[1]);

            $description = mb_convert_encoding($description, 'UTF-8', 'auto');
            $description = preg_replace('/[^a-zA-Z0-9\s.,()\-\_]/u', ' ', $description);
            $description = preg_replace('/\s+/', ' ', $description);

            ActualBillOfMaterial::create([
                'item_code' => $itemCode,
                'description' => $description,
                'uom' => $row[2],
                'quantity' => $row[3],
                'warehouse' => $row[4],
                'depth' => $row[7],
                'bom_type' => $row[8]
            ]);
            $addedItems[] = $itemCode;

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-actual-bom-progress', $progress, now()->addMinutes(5));
        }

        Cache::put('import-actual-bom-progress', 100, now()->addMinutes(5));

        return redirect()->route('ac.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function getPaginated(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');
        $itemCodeFilter = $request->input('item_code_filter');
        $descriptionFilter = $request->input('description_filter');
        $sortField = $request->input('sort_field', 'item_code'); // Default sort field
        $sortOrder = $request->input('sort_order', 'asc');

        $bomPaginated = ActualBillOfMaterial::where('depth', 1)
            ->when($searchTerm, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('item_code', 'LIKE', '%' . $term . '%');
                    $q->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            })
            ->when($itemCodeFilter, function ($query, $term) {
                $query->where('item_code', 'LIKE', $term . '%');
            })
            ->when($descriptionFilter, function ($query, $term) {
                $query->where('description', 'LIKE', '%' . $term . '%');
            })
            ->orderBy($sortField, $sortOrder)

            ->paginate($perPage);

        return response()->json($bomPaginated);
    }

    public function getComponent(Request $request)
    {
        // Pastikan Anda mendapatkan ID dari query parameter (misal: /api/standard/get-component?id=123)
        $id = $request->input('id');

        if (!$id) {
            // Jika ID tidak ada, kembalikan response error atau kosong
            return response()->json(['error' => 'Component ID is required'], 400);
        }

        // Optimasi: Jika tabelnya besar, hindari fetch()->get() tanpa kriteria filter
        // Namun, jika struktur BOM Anda adalah tree yang di-flatten, pendekatan ini (fetch all) mungkin perlu dipertahankan.

        // 1. Ambil semua data
        $all = ActualBillOfMaterial::orderBy('id')->get();

        // 2. Cari index dari Item Finish Good yang diklik
        $mainIndex = $all->search(fn($item) => $item->id == $id);

        if ($mainIndex === false) {
            return response()->json([]);
        }

        $components = collect();

        // 3. Iterasi untuk mengambil semua komponen di bawahnya sampai bertemu 'depth' 1 lagi
        for ($i = $mainIndex + 1; $i < count($all); $i++) {
            // Asumsi: depth 1 adalah item level tertinggi (finish good)
            if ($all[$i]->depth == 1) break;

            $components->push($all[$i]);
        }

        // $finishGood = $all[$mainIndex]; // Ini tidak perlu di-return, tapi bagus untuk referensi

        // Mengembalikan data komponen
        return response()->json($components);
    }
}
