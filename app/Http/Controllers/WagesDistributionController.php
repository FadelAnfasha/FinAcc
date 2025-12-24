<?php

namespace App\Http\Controllers;

use App\Models\WagesDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class WagesDistributionController extends Controller
{
    public function import(Request $request)
    {
        // 1. Validasi File
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $csvData = [];

        // 2. Baca CSV dengan Delimiter ;
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';
            $header = fgetcsv($handle, 1000, $delimiter); // Lewati header

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Validasi minimal kolom (sesuai kebutuhan Anda yaitu 18 kolom)
                if (count($row) >= 18) {
                    $csvData[] = $row;
                }
            }
            fclose($handle);
        } else {
            return redirect()->route('pc.master')->withErrors(['file' => 'Gagal membuka file CSV.']);
        }

        if (empty($csvData)) {
            return back()->withErrors(['file' => 'File kosong atau format tidak sesuai.']);
        }

        // Ambil baris pertama data (asumsi Anda hanya memproses satu baris data sesuai logic awal)
        $row = $csvData[0];
        Cache::put('import-progress-wd', 10, now()->addMinutes(2));

        /**
         * Helper Fungsi untuk membersihkan angka
         * Menghapus koma (ribuan) agar PHP bisa melakukan operasi matematika dengan titik (desimal)
         */
        $cleanNumber = function ($value) {
            // Hapus koma (ribuan)
            $clean = str_replace(',', '', $value);
            // Pastikan menjadi float, lalu gunakan ceil sesuai logic Anda
            return ceil((float)$clean * 100) / 100;
        };

        // 3. Mapping Data dengan Pembersihan Angka
        $importedData = [
            'blanking'      => $cleanNumber($row[0]),
            'spinDisc'      => $cleanNumber($row[1]),
            'autoDisc'      => $cleanNumber($row[2]),
            'manualDisc'    => $cleanNumber($row[3]),
            'discLathe'     => $cleanNumber($row[4]),
            'rim1'          => $cleanNumber($row[5]),
            'rim2'          => $cleanNumber($row[6]),
            'rim3'          => $cleanNumber($row[7]),
            'coiler'        => $cleanNumber($row[8]),
            'forming'       => $cleanNumber($row[9]),
            'assy1'         => $cleanNumber($row[10]),
            'assy2'         => $cleanNumber($row[11]),
            'machining'     => $cleanNumber($row[12]),
            'shotPeening'   => $cleanNumber($row[13]),
            'ced'           => $cleanNumber($row[14]),
            'topcoat'       => $cleanNumber($row[15]),
            'packing_dom'   => $cleanNumber($row[16]),
            'packing_exp'   => $cleanNumber($row[17]),
        ];

        $addedItems = [];
        $updatedItems = [];

        // 4. Proses Simpan/Update
        $existing = WagesDistribution::first();

        if ($existing) {
            foreach ($importedData as $field => $newValue) {
                // Bandingkan dengan round untuk menghindari selisih kecil floating point
                if (round((float)$existing->$field, 2) != round($newValue, 2)) {
                    $updatedItems[] = $field . ' : ' . $newValue;
                }
            }
            $existing->update($importedData);
        } else {
            WagesDistribution::create($importedData);
            foreach ($importedData as $field => $value) {
                $addedItems[] = $field . ' : ' . $value;
            }
        }

        Cache::put('import-progress-wd', 100, now()->addMinutes(2));

        return redirect()->route('pc.master')
            ->with('addedItems', $addedItems)
            ->with('updatedItems', $updatedItems);
    }

    public function update(Request $request)
    {
        // dd($request->all()); // Debugging, hapus nanti  
        $validated = $request->validate([
            'blanking' => 'required',
            'spinDisc' => 'required',
            'autoDisc' => 'required',
            'manualDisc' => 'required',
            'discLathe' => 'required',
            'rim1' => 'required',
            'rim2' => 'required',
            'rim3' => 'required',
            'coiler' => 'required',
            'forming' => 'required',
            'assy1' => 'required',
            'assy2' => 'required',
            'machining' => 'required',
            'shotPeening' => 'required',
            'ced' => 'required',
            'topcoat' => 'required',
            'packing_dom' => 'required',
            'packing_exp' => 'required',
        ]);

        WagesDistribution::truncate();

        WagesDistribution::insert($validated);

        return redirect()->route('pc.master')->with([
            'success' => 'Data updated successfully.',
        ]);
    }
}
