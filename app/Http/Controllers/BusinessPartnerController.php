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
            return redirect()->route('pc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('pc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        BusinessPartner::truncate();
        foreach ($csvData as $row) {
            $bpCode = trim($row[1]);
            $bpName = trim($row[2]);

            $codeCounts[$bpCode] = ($codeCounts[$bpCode] ?? 0) + 1;
            $nameCounts[$bpName] = ($nameCounts[$bpName] ?? 0) + 1;
        }

        foreach ($csvData as $index => $row) {
            $bpCode = trim($row[1]);
            $bpName = trim($row[2]);

            if (($codeCounts[$bpCode] ?? 0) > 1 || ($nameCounts[$bpName] ?? 0) > 1) {
                // $invalidItems[] = "{$bpCode} - {$bpName} (Duplicate in file)";
                $invalidItems[] = ['bp_code' => $bpCode, 'reason' => 'Duplicate in file.'];
                continue;
            }

            $isValid = preg_match('/^C[A-Z]\d{4}$/', $bpCode);
            $matchesNameInitial = (
                strlen($bpCode) >= 2 &&
                strlen($bpName) > 0 &&
                strtoupper($bpCode[1]) === strtoupper($bpName[0])
            );

            if (!$isValid || !$matchesNameInitial) {
                // $invalidItems[] = "{$bpCode} - {$bpName} (Invalid code format)";
                $invalidItems[] = [
                    'bp_code' => $bpCode,
                    'bp_name' => $bpName,
                    'reason' => 'Invalid code format.',
                ];
                continue;
            }

            if (BusinessPartner::where('bp_code', $bpCode)->exists()) {
                // $invalidItems[] = "{$bpCode} - {$bpName} (BP Code already exists)";
                $invalidItems[] = ['bp_code' => $bpCode, 'bp_name' => $bpName, 'reason' => 'BP Code already exist.'];
                continue;
            }

            if (BusinessPartner::where('bp_name', $bpName)->exists()) {
                // $invalidItems[] = "{$bpCode} - {$bpName} (BP Name already exists)";
                $invalidItems[] = ['bp_code' => $bpCode, 'bp_name' => $bpName, 'reason' => 'BP Name already exist.'];
                continue;
            }

            BusinessPartner::create([
                'bp_code' => $bpCode,
                'bp_name' => $bpName
            ]);
            $addedItems[] = $bpCode;

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-bp', $progress, now()->addMinutes(5));
        }

        Cache::put('import-progress-bp', 100, now()->addMinutes(5));

        return redirect()->route('pc.master')->with([
            'success' => 'CSV import process completed!',
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
