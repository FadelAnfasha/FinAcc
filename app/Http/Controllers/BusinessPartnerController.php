<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class BusinessPartnerController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file));

        $header = array_shift($csvData); // Skip header

        $addedItems = [];
        $invalidItems = [];

        $codeCounts = [];
        $nameCounts = [];

        $total = count($csvData);

        // Hitung duplikat di file
        foreach ($csvData as $row) {
            $bpCode = trim($row[0]);
            $bpName = trim($row[1]);

            $codeCounts[$bpCode] = ($codeCounts[$bpCode] ?? 0) + 1;
            $nameCounts[$bpName] = ($nameCounts[$bpName] ?? 0) + 1;
        }

        foreach ($csvData as $index => $row) {
            $bpCode = trim($row[0]);
            $bpName = trim($row[1]);

            // Duplikat dalam file
            if ($codeCounts[$bpCode] > 1 || $nameCounts[$bpName] > 1) {
                $invalidItems[] = "{$bpCode} - {$bpName} (Duplicate in file)";
                continue;
            }

            // Validasi format kode & inisial huruf
            $isValid = preg_match('/^C[A-Z]\d{4}$/', $bpCode);
            $matchesNameInitial = (
                strlen($bpCode) >= 2 &&
                strlen($bpName) > 0 &&
                strtoupper($bpCode[1]) === strtoupper($bpName[0])
            );

            if (!$isValid || !$matchesNameInitial) {
                $invalidItems[] = "{$bpCode} - {$bpName} (Format validation failed)";
                continue;
            }

            // ✅ CEK 1: Kode sudah ada di database?
            if (BusinessPartner::where('bp_code', $bpCode)->exists()) {
                $invalidItems[] = "{$bpCode} - {$bpName} (BP Code already exists)";
                continue;
            }

            // ✅ CEK 2: Nama sudah ada di database?
            if (BusinessPartner::where('bp_name', $bpName)->exists()) {
                $invalidItems[] = "{$bpCode} - {$bpName} (BP Name already exists)";
                continue;
            }

            // ✅ Data valid → simpan
            BusinessPartner::create([
                'bp_code' => $bpCode,
                'bp_name' => $bpName
            ]);
            $addedItems[] = $bpCode;

            // Progress (optional)
            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-bp', $progress, now()->addMinutes(5));
        }

        Cache::put('import-progress-bp', 100, now()->addMinutes(5));

        return redirect()->route('pc.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function destroy($bp_code)
    {
        $bp = BusinessPartner::findOrFail($bp_code);
        $bp->delete();

        return redirect()->route('pc.master')->with('deleted', 'Business Partner ' . $bp_code . ' deleted successfully');
    }

    public function store(Request $request)
    {
        $companyName = strtoupper(trim($request->input('bp_name')));
        $companyType = strtoupper(trim($request->input('company_type')));
        $bp_name = $companyName . ', ' . $companyType . '.';

        // Cek apakah nama BP sudah ada di database
        $nameExists = BusinessPartner::where('bp_name', $bp_name)->exists();
        if ($nameExists) {
            return back()->with('deleted', 'Business Partner name already exists.')->withInput();
        }

        // Generate item_no (BP Code)
        $prefix = 'C' . strtoupper(substr($companyName, 0, 1));
        $bps = BusinessPartner::where('bp_code', 'like', $prefix . '%')->orderBy('bp_code', 'asc')->get();

        $existingNumbers = $bps->map(function ($bp) use ($prefix) {
            return intval(substr($bp->bp_code, strlen($prefix)));
        })->toArray();

        $newNumber = '001';
        for ($i = 1; $i <= count($existingNumbers) + 1; $i++) {
            if (!in_array($i, $existingNumbers)) {
                $newNumber = str_pad($i, 4, '0', STR_PAD_LEFT);
                break;
            }
        }

        $item_no = $prefix . $newNumber;

        // Simpan data baru
        BusinessPartner::create([
            'bp_code' => $item_no,
            'bp_name' => $bp_name,
        ]);

        return redirect()->route('pc.master');
    }
}
