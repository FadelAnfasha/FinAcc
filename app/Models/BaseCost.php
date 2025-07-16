<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseCost extends Model
{
    protected $table = 'baseCosts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bp_code',
        'item_code',
        'blanking',
        'spinDisc',
        'autoDisc',
        'manualDisc',
        'discLathe',
        'total_disc',

        'rim1',
        'rim2',
        'rim3',
        'total_rim',

        'coiler',
        'forming',
        'total_sidering',

        'assy1',
        'assy2',
        'machining',
        'shotPeening',
        'total_assy',

        'ced',
        'topcoat',
        'total_painting',

        'packing_dom',
        'packing_exp',
        'total_packaging',

        'total'
    ];

    public function bp()
    {
        return $this->belongsTo(BusinessPartner::class, 'bp_code', 'bp_code');
    }

    public function item()
    {
        return $this->belongsTo(CycleTime::class, 'item_code', 'item_code');
    }
}
