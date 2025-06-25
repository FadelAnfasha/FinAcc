<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CycleTime extends Model
{
    use HasFactory;

    protected $table = 'cycle_time';
    protected $primaryKey = 'item_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_code',
        'size',
        'type',
        'blanking',
        'blanking_eff',
        'spinDisc',
        'spinDisc_eff',
        'autoDisc',
        'autoDisc_eff',
        'manualDisc',
        'manualDisc_eff',
        'c3_sn',
        'c3_sn_eff',
        'repairC3',
        'repairC3_eff',
        'discLathe',
        'discLathe_eff',
        'rim1',
        'rim1_eff',
        'rim2',
        'rim2_eff',
        'rim2insp',
        'rim2insp_eff',
        'rim3',
        'rim3_eff',
        'coiler',
        'coiler_eff',
        'forming',
        'forming_eff',
        'assy1',
        'assy1_eff',
        'assy2',
        'assy2_eff',
        'machining',
        'machining_eff',
        'shotPeening',
        'shotPeening_eff',
        'ced',
        'ced_eff',
        'topcoat',
        'topcoat_eff',
        'packing_dom',
        'packing_exp',
    ];

    public static function validateEfficiencies($data)
    {
        $efficiencyFields = array_filter(array_keys($data), fn($key) => str_ends_with($key, '_eff'));

        foreach ($efficiencyFields as $field) {
            if (!is_numeric($data[$field]) || $data[$field] < 0 || $data[$field] > 1) {
                return false;
            }
        }

        return true;
    }

    public function salesQty()
    {
        return $this->hasMany(SalesQuantity::class, 'item_code', 'item_code');
    }
}
