<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActualCost extends Model
{
    protected $table = 'actual_cost';
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

    public function bom()
    {
        return $this->belongsTo(BillOfMaterial::class, 'item_code', 'item_code');
    }

    public function ct()
    {
        return $this->belongsTo(CycleTime::class, 'item_code', 'item_code');
    }

    public function discWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_disc', 'item_code');
    }

    public function discMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class, 'disc_code', 'item_code');
    }

    public function discProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_disc', 'item_code');
    }

    public function rimWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_rim', 'item_code');
    }

    public function rimMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class, 'rim_code', 'item_code');
    }

    public function rimProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_rim', 'item_code');
    }

    public function sideringWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_sidering', 'item_code');
    }

    public function sideringMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class, 'sidering_code', 'item_code');
    }

    public function sideringProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_sidering', 'item_code');
    }

    public function assyWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_assy', 'item_code');
    }

    public function assyProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_assy', 'item_code');
    }

    public function cedWWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_cedW', 'item_code');
    }

    public function cedWProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_cedW', 'item_code');
    }

    public function cedSRWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_cedSR', 'item_code');
    }

    public function cedSRProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_cedSR', 'item_code');
    }

    public function tcWWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_tcW', 'item_code');
    }

    public function tcWProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_tcW', 'item_code');
    }

    public function tcSRWIP()
    {
        return $this->belongsTo(BillOfMaterial::class, 'wip_tcSR', 'item_code');
    }

    public function tcSRProcess()
    {
        return $this->belongsTo(BillOfMaterial::class, 'pr_tcSR', 'item_code');
    }
}
