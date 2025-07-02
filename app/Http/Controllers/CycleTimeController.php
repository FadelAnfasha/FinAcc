<?php

namespace App\Http\Controllers;

use App\Models\CycleTime;
use Illuminate\Http\Request;

class CycleTimeController extends Controller
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
        $invalidItems = [];

        // Hitung duplikasi di CSV
        $codeCounts = [];
        foreach ($csvData as $row) {
            $itemCode = trim($row[0]);
            $codeCounts[$itemCode] = ($codeCounts[$itemCode] ?? 0) + 1;
        }

        foreach ($csvData as $row) {
            $itemCode = trim($row[0]);

            // Cek duplikat dalam file
            if ($codeCounts[$itemCode] > 1) {
                $invalidItems[] = "{$itemCode} (Duplicated in file)";
                continue;
            }

            // Validasi format kode
            $isValid = preg_match('/^F(\d{2})([DMNRTW])(\d{2})$/', $itemCode, $matches);
            $midDigits = isset($matches[1]) ? intval($matches[1]) : null;

            if (!$isValid || $midDigits < 15 || $midDigits > 24) {
                $invalidItems[] = "{$itemCode} (Invalid format)";
                continue;
            }

            // Lanjutkan proses simpan
            $existing = CycleTime::where('item_code', $itemCode)->first();

            $newData = [
                'size' => $row[1],
                'type' => $row[2],
                'blanking' => $row[3],
                'blanking_eff' => $row[4],
                'spinDisc' => $row[5],
                'spinDisc_eff' => $row[6],
                'autoDisc' => $row[7],
                'autoDisc_eff' => $row[8],
                'manualDisc' => $row[9],
                'manualDisc_eff' => $row[10],
                'c3_sn' => $row[11],
                'c3_sn_eff' => $row[12],
                'repairC3' => $row[13],
                'repairC3_eff' => $row[14],
                'discLathe' => $row[15],
                'discLathe_eff' => $row[16],
                'rim1' => $row[17],
                'rim1_eff' => $row[18],
                'rim2' => $row[19],
                'rim2_eff' => $row[20],
                'rim2insp' => $row[21],
                'rim2insp_eff' => $row[22],
                'rim3' => $row[23],
                'rim3_eff' => $row[24],
                'coiler' => $row[25],
                'coiler_eff' => $row[26],
                'forming' => $row[27],
                'forming_eff' => $row[28],
                'assy1' => $row[29],
                'assy1_eff' => $row[30],
                'assy2' => $row[31],
                'assy2_eff' => $row[32],
                'machining' => $row[33],
                'machining_eff' => $row[34],
                'shotPeening' => $row[35],
                'shotPeening_eff' => $row[36],
                'ced' => $row[37],
                'ced_eff' => $row[38],
                'topcoat' => $row[39],
                'topcoat_eff' => $row[40],
                'packing_dom' => $row[41],
                'packing_exp' => $row[42],
            ];

            if (!$existing) {
                CycleTime::create(array_merge(['item_code' => $itemCode], $newData));
                $addedItems[] = $itemCode;
            } else {
                $isUpdated = false;
                foreach ($newData as $key => $value) {
                    if ($existing->{$key} != $value) {
                        $isUpdated = true;
                        break;
                    }
                }

                if ($isUpdated) {
                    $existing->update($newData);
                    $updatedItems[] = $itemCode;
                }
            }
        }

        return redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function destroy($item_code)
    {
        $ct = CycleTime::findOrFail($item_code);
        $ct->delete();

        return redirect()->route('pc.master')->with('deleted', 'Cycle Time ' . $item_code . ' deleted successfully');
    }
}
