<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BOM_Report extends Model
{
    protected $table = 'bom_reports';
    protected $primaryKey = 'item_code';
    public $incrementing = false; // Karena bp_code bukan integer auto-increment
    protected $keyType = 'string';
    protected $fillable = [
        'item_code',
        'disc_qty',
        'disc_code',
        'disc_price',
        'rim_qty',
        'rim_code',
        'rim_price',
        'sidering_qty',
        'sidering_code',
        'sidering_price',
        'pr_disc',
        'pr_disc_price',
        'pr_rim',
        'pr_rim_price',
        'pr_sidering',
        'pr_sidering_price',
        'pr_assy',
        'pr_assy_price',
        'pr_cedW',
        'pr_cedW_price',
        'pr_cedSR',
        'pr_cedSR_price',
        'pr_tcW',
        'pr_tcW_price',
        'pr_tcSR',
        'pr_tcSR_price',
        'pack_price',
        'wip_disc',
        'wip_disc_price',
        'wip_rim',
        'wip_rim_price',
        'wip_sidering',
        'wip_sidering_price',
        'wip_assy',
        'wip_assy_price',
        'wip_cedW',
        'wip_cedW_price',
        'wip_cedSR',
        'wip_cedSR_price',
        'wip_tcW',
        'wip_tcW_price',
        'wip_tcSR',
        'wip_tcSR_price',
        'wip_valve',
        'wip_valve_price',
        'total_raw_material',
        'total_process',
        'total'
    ];
}
