<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProcessController extends Controller
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
            // Ambil process berdasarkan item_code
            $process = Process::where('item_code', $row[0])->first();

            // Persiapkan data baru untuk dibandingkan
            $newData = [
                'description' => $row[1] ?? '-', // Jika tidak ada deskripsi, gunakan string kosong
                'price' => is_numeric($row[2]) ? floatval($row[2]) : 0.0,
                'manufacturer' => $row[3],
            ];

            // Jika process belum ada, maka data baru akan ditambahkan
            if (!$process) {
                $process = Process::create([
                    'item_code' => $row[0],
                    'description' => $row[1] ?? '-',
                    'price' => is_numeric($row[2]) ? floatval($row[2]) : 0.0,
                    'manufacturer' => $row[3],
                ]);
                $addedItems[] = $process->item_code; // Tambahkan ke daftar addedItems
            } else {
                // Jika process ada, periksa apakah ada perubahan pada data
                $isUpdated = false;

                // Bandingkan data lama dengan data baru
                foreach ($newData as $key => $value) {
                    if ($process->{$key} != $value) {
                        $isUpdated = true;
                        break;
                    }
                }

                // Jika ada perubahan, update data dan masukkan ke updatedItems
                if ($isUpdated) {
                    $process->update($newData);
                    $updatedItems[] = $process->item_code; // Tambahkan ke daftar updatedItems
                }
            }
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-proc', $progress, now()->addMinutes(5));
        }
        Cache::put('import-progress-proc', 100, now()->addMinutes(5));

        // Kirim data feedback ke view
        redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
        ]);
    }

    public function destroy($item_code)
    {
        $process = Process::findOrFail($item_code);
        $process->delete();

        redirect()->route(route: 'pc.master');
    }

    public function export()
    {
        $processes = Process::all();

        $filename = 'Processes_Master' . date('Y') . '-' . date('m') . '-' . date('d') . '.csv';

        return response()->streamDownload(function () use ($processes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item No', 'Description', 'Price', 'Item Group']);

            foreach ($processes as $process) {
                fputcsv($handle, [
                    $process->item_code,
                    $process->description,
                    $process->price,
                    $process->manufacturer,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function edit($item_code)
    {
        $process = Process::findOrFail($item_code);

        return view('processes.edit', compact('process'));
    }

    public function update(Request $request, $item_code)
    {
        // Validasi input
        $request->validate([
            'price' => 'required'
        ]);

        $price = $request->input('price');
        $manufacturer = $request->input('manufacturer');

        // Temukan process berdasarkan item_code dan perbarui
        $process = Process::findOrFail($item_code);
        $process->update([
            'price' => $price, // Menggunakan nilai price yang sudah diolah
            'manufacturer' => $manufacturer,
        ]);

        redirect()->route(route: 'pc.master');
    }

    public function create()
    {
        return view('materials.create');
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
        $materials = Process::where('item_code', 'like', $prefix . '%')->orderBy('item_code', 'asc')->get();

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
        Process::create([
            'item_code' => $item_code,
            // 'item_desc' => $request->input('item_desc'),
            'in_stock' => $in_stock,
            'item_group' => $request->input('item_group'),
            'price' => $price,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material added successfully with code : ' . $item_code);
    }
}
