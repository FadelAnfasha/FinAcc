<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'diff_cost_x_sales_quantities';

        // --- BLOK 1: PERUBAHAN SKEMA ---
        Schema::table($tableName, function (Blueprint $table) {
            // Drop unique constraint lama
            $table->dropUnique('diff_cost_unique');

            // Ganti nama kolom dari 'difference_period' menjadi 'period'
            $table->renameColumn('difference_period', 'period');
        });

        // --- BLOK 2: DATA MANIPULATION (Transformasi Format) ---

        // Ambil semua data yang perlu diubah. Kita harus SELECT kolom sales_month juga.
        $records = DB::table($tableName)->select('id', 'period', 'sales_month')->get();

        foreach ($records as $record) {
            $oldPeriod = $record->period; // Contoh: "Jan'2025 - Aug'2025"
            // Pastikan kolom sales_month benar-benar ada di tabel Anda
            $salesMonth = $record->sales_month;
            $newPeriod = $oldPeriod; // Default ke nilai lama

            // Logika transformasi: Ekstrak Periode Akhir Penuh
            if (str_contains($oldPeriod, ' - ')) {
                // Pisahkan string di tanda hubung
                $parts = explode(' - ', $oldPeriod);

                // Ambil bagian terakhir (Periode Akhir Penuh: Aug'2025)
                $endPeriodFull = trim(end($parts));

                // Bentuk format baru: "Aug'2025 / Aug"
                $newPeriod = "{$endPeriodFull} / {$salesMonth}";
            } elseif (str_contains($oldPeriod, "'")) {
                // Tangani kasus yang hanya berisi satu periode (ex: "Jan'2025")
                // Asumsi: Periode penuh adalah periode akhir
                $newPeriod = "{$oldPeriod} / {$salesMonth}";
            }

            // Jika transformasi berhasil dan berbeda, update
            if ($newPeriod !== $oldPeriod) {
                DB::table($tableName)
                    ->where('id', $record->id)
                    ->update(['period' => $newPeriod]);
            }
        }

        // --- BLOK 3: TAMBAHKAN KENDALA BARU ---
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('sales_month');
            $table->unique(['item_code', 'period'], 'diff_cost_period_unique');
        });
    }


    public function down(): void
    {
        $tableName = 'diff_cost_x_sales_quantities';
        Schema::table($tableName, function (Blueprint $table) {
            // Hapus unique constraint baru
            $table->dropUnique('diff_cost_period_unique');

            // Tambahkan kembali kolom sales_month yang dihapus di up()
            // Asumsi: sales_month adalah string (varchar)
            $table->string('sales_month')->nullable()->after('period');
        });
        $records = DB::table($tableName)->select('id', 'period')->get();

        foreach ($records as $record) {
            $currentPeriod = $record->period;
            $oldPeriodFormat = $currentPeriod;

            if (str_contains($currentPeriod, ' / ')) {
                $parts = explode(' / ', $currentPeriod);
                $endPeriodFull = trim($parts[0]);
                $oldPeriodFormat = $endPeriodFull;
            }

            if ($oldPeriodFormat !== $currentPeriod) {
                DB::table($tableName)
                    ->where('id', $record->id)
                    ->update(['period' => $oldPeriodFormat]);
            }
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->renameColumn('period', 'difference_period');
            $table->unique(['item_code', 'difference_period'], 'diff_cost_unique');
        });
    }
};
