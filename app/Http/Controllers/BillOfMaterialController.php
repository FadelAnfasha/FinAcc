<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use Illuminate\Http\Request;
use Symfony\Component\String\TruncateMode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class BillOfMaterialController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv'
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $processedCsvData = [];

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $bom = fread($handle, 3);
            if (pack('CCC', 0xef, 0xbb, 0xbf) !== $bom) {
                rewind($handle);
            }

            $delimiter = ',';
            $enclosure = '"';
            $escape = '\\';

            fgetcsv($handle, 0, $delimiter, $enclosure, $escape); // Lewati header

            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== FALSE) {
                if (empty(array_filter($row))) {
                    continue;
                }
                $processedCsvData[] = $row;
            }
            fclose($handle);
        } else {
            return redirect()->back()->with('error', 'Could not open CSV file.');
        }

        $addedItems = [];
        $updatedItems = [];
        $invalidItems = [];

        BillOfMaterial::truncate();

        $validGroup = false;
        Session::put('validGroup', $validGroup);

        $total = count($processedCsvData);

        foreach ($processedCsvData as $index => $data) {

            $order = intval($data[0] ?? 0);
            $item_code = trim($data[0] ?? '');
            $description = trim($data[1] ?? ''); // Default ke string kosong & langsung trim
            $uom = trim($data[2] ?? '-');
            $quantity = is_numeric($data[3] ?? null) ? floatval(trim($data[3])) : 0.0;
            $warehouse = trim($data[4] ?? '');
            $depth = isset($data[7]) ? intval(trim($data[7])) : null;
            $bom_type = trim($data[8] ?? '');

            $originalEncoding = 'Windows-1252';
            // dump([
            //     'row_index' => $index + 2, // Baris di CSV, dimulai dari 1 (header dilewati), jadi +2
            //     'description_from_csv' => $description,
            //     'hex_from_csv' => bin2hex($description), // Lihat representasi hex-nya
            // ]);
            $description = mb_convert_encoding($description, 'UTF-8', $originalEncoding);
            // dump([
            //     'row_index' => $index + 2,
            //     'description_after_mb_convert' => $description,
            //     'hex_after_mb_convert' => bin2hex($description),
            //     'mb_detect_encoding_after_mb_convert' => mb_detect_encoding($description, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true),
            // ]);

            $description = preg_replace('/[\x{00D8}\x{00F8}\x{2300}]/u', '-O-', $description);
            $description = str_replace(
                ["\x{00D8}", "\x{00F8}", "\x{2300}"], // Karakter Unicode Ø, ø, ⌀
                '-O-',
                $description
            );
            // DEBUG 4: Setelah preg_replace
            // dump([
            //     'row_index' => $index + 2,
            //     'description_after_preg_replace' => $description,
            //     'hex_after_preg_replace' => bin2hex($description), // Jika ini kosong atau null, preg_replace gagal
            // ]);
            $description = iconv('UTF-8', 'UTF-8//IGNORE', $description);
            // DEBUG 5: Setelah iconv
            // dump([
            //     'row_index' => $index + 2,
            //     'description_after_iconv' => $description,
            //     'hex_after_iconv' => bin2hex($description),
            // ]);

            // dump([
            //     'Item Code' => $item_code,
            //     'Description' => $description,
            //     'UoM' => $uom,
            //     'Depth' => $depth,
            //     'Valid Group' => $validGroup,
            //     'Check item_code' => empty($item_code),
            //     'Check description' => empty($description),
            //     'Check uom' => empty($uom),
            //     'Check depth' => is_null($depth) // atau $depth === null
            // ]);


            if (empty($item_code) || empty($description) || empty($uom) || $depth === null) {
                $invalidItems[] = "Row " . ($index + 2) . ": Missing critical data (Item Code, Description, UoM, Depth). Original: " . json_encode($data);
                Log::warning("Skipping BOM row " . ($index + 2) . " due to missing critical data: " . json_encode($data));
                continue;
            }

            if ($depth === 1) {
                $validGroup = false;
                $isValid = preg_match('/^F(\d{2})([DMNRTW])(\d{2})$/', $item_code, $matches);
                $midDigits = isset($matches[1]) ? intval($matches[1]) : null;

                if (!$isValid || $midDigits < 15 || $midDigits > 24) {
                    Session::put('validGroup', $validGroup);
                    $invalidItems[] = $item_code . " (Invalid Item Code format or mid-digits for Depth 1)";
                    Log::warning("Invalid Depth 1 item_code format: " . $item_code);
                    continue;
                }

                $validGroup = true;
                Session::put('validGroup', $validGroup);
            }


            if (($depth > 1 && $validGroup) || ($depth === 1 && $validGroup)) {
                try {
                    if ($item_code === 'CI0004') {
                        dump(
                            [
                                'Item Code' => $item_code,
                                'Description' => $description,
                                'UoM' => $uom,
                                'Depth' => $depth,
                                'Valid Group' => $validGroup
                            ]
                        );
                    }
                    BillOfMaterial::create([
                        'item_code' => $item_code,
                        'description' => $description,
                        'uom' => $uom,
                        'quantity' => $quantity,
                        'warehouse' => $warehouse,
                        'depth' => $depth,
                        'bom_type' => $bom_type,
                        'created_at' => now()->addMicroseconds($order),
                    ]);
                    $addedItems[] = $item_code;
                } catch (\Exception $e) {
                    $invalidItems[] = $item_code . " (Database save error: " . $e->getMessage() . "). Original: " . json_encode($data);
                    Log::error("Failed to save BOM item " . $item_code . ": " . $e->getMessage() . " Data: " . json_encode($data));
                }
            } else {
                $invalidItems[] = $item_code . " (Skipped due to invalid group or missing Depth 1 parent)";
                Log::info("Skipping BOM item " . $item_code . " due to validGroup condition. Data: " . json_encode($data));
            }

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-progress-bom', $progress, now()->addMinutes(5));
        }

        Cache::put('import-progress-bom', 100, now()->addMinutes(5));

        return redirect()->route('bom.master')->with([
            'success' => 'CSV file imported successfully',
            'addedItems' => $addedItems,
            'updatedItems' => $updatedItems,
            'invalidItems' => $invalidItems
        ]);
    }
    public function components($id)
    {
        $all = BillOfMaterial::orderBy('id')->get();
        $mainIndex = $all->search(fn($item) => $item->id == $id);

        if ($mainIndex === false) {
            return response()->json([]);
        }

        $components = collect();
        for ($i = $mainIndex + 1; $i < count($all); $i++) {
            if ($all[$i]->depth == 1) break;
            $components->push($all[$i]);
        }

        $finishGood = $all[$mainIndex];
        return redirect()->route('bom.master', ['component_id' => $id]);
    }
}
