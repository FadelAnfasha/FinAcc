<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesQuantity extends Model
{
    protected $table = 'sales_quantity';

    protected $fillable = [
        'bp_code',
        'item_code',
        'quantity',
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
