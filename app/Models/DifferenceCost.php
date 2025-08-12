<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DifferenceCost extends Model
{
    protected $table = 'difference_cost';
    protected $fillable = [
        'item_code',
        'standard_year',
        'standard_month',
        'actual_year',
        'actual_month',

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
        return $this->belongsTo(StandardCost::class, 'item_code', 'item_code');
    }
}
