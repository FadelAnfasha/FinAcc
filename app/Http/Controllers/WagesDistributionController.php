<?php

namespace App\Http\Controllers;

use App\Models\WagesDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WagesDistributionController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file));

        // Buang header
        $header = array_shift($csvData);


        // Ambil data CSV (asumsi hanya 1 baris)
        $row = $csvData[0];

        if (count($row) < 18) {
            return back()->withErrors(['file' => 'Format CSV tidak valid. Kolom tidak lengkap.']);
        }

        $importedData = [
            'blanking'      => ceil($row[0] * 100) / 100,
            'spinDisc'  => ceil($row[1] * 100) / 100,
            'autoDisc'      => ceil($row[2] * 100) / 100,
            'manualDisc'    => ceil($row[3] * 100) / 100,
            'discLathe'     => ceil($row[4] * 100) / 100,
            'rim1'          => ceil($row[5] * 100) / 100,
            'rim2'          => ceil($row[6] * 100) / 100,
            'rim3'          => ceil($row[7] * 100) / 100,
            'coiler'        => ceil($row[8] * 100) / 100,
            'forming'       => ceil($row[9] * 100) / 100,
            'assy1'         => ceil($row[10] * 100) / 100,
            'assy2'         => ceil($row[11] * 100) / 100,
            'machining'     => ceil($row[12] * 100) / 100,
            'shotPeening'   => ceil($row[13] * 100) / 100,
            'ced'           => ceil($row[14] * 100) / 100,
            'topcoat'       => ceil($row[15] * 100) / 100,
            'packing_dom'   => ceil($row[16] * 100) / 100,
            'packing_exp'   => ceil($row[17] * 100) / 100,
        ];

        $addedItems = [];
        $updatedItems = [];
        // Cek data lama
        $existing = DB::table('wages_distribution')->first();

        if ($existing) {
            foreach ($importedData as $field => $newValue) {
                if (round($existing->$field, 2) != $newValue) {
                    $updatedItems[] = $field . ' - ' . $newValue;
                }
            }

            // Update saja tanpa truncate
            DB::table('wages_distribution')->update($importedData);
        } else {
            // Kalau belum ada data, buat baru
            WagesDistribution::create($importedData);

            // Semua field dianggap "added"
            foreach (array_keys($importedData) as $field) {
                $addedItems[] = $field . ' - ' . $importedData[$field];
            }
        }

        return redirect()->route('pc.master')
            ->with('addedItems', $addedItems)
            ->with('updatedItems', $updatedItems);
    }
}
