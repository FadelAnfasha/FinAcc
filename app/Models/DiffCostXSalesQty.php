<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiffCostXSalesQty extends Model
{
    protected $table = 'diff_cost_x_sales_quantities';

    protected $fillable = [
        'item_code',
        'difference_period',
        'sales_month',
        'quantity',
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

    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
