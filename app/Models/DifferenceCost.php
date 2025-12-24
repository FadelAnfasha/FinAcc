<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DifferenceCost extends Model
{
    protected $table = 'difference_cost';
    protected $fillable = [
        'item_code',
        'period',
        'total_raw_material',
        'total_process',
        'total'
    ];

    public function sc()
    {
        return $this->belongsTo(StandardCost::class, 'item_code', 'item_code');
    }

    public function ac()
    {
        return $this->belongsTo(ActualCost::class, 'item_code', 'item_code');
    }

    public function bom()
    {
        return $this->hasOne(ActualBillOfMaterial::class, 'item_code', 'item_code');
    }
}
