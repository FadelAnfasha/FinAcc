<?php

namespace App\Models;

use App\Http\Controllers\BOMController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillOfMaterial extends Model
{
    use HasFactory;

    protected $table = 'bom';

    protected $fillable = [
        'item_code',
        'description',
        'uom',
        'quantity',
        'warehouse',
        'depth',
        'bom_type',
        'created_at',
        'updated_at',
    ];

    public function materialInfo()
    {
        return $this->hasOne(Material::class, 'item_code', 'item_code');
    }

    public function processInfo()
    {
        return $this->hasOne(Process::class, 'item_code', 'item_code');
    }

    public function packingInfo()
    {
        return $this->hasOne(Packing::class, 'item_code', 'item_code');
    }

    public function valveInfo()
    {
        return $this->hasOne(Valve::class, 'item_code', 'item_code');
    }

    public function processCost()
    {
        return $this->hasOne(ProcessCost::class, 'item_code', 'item_code');
    }

    public function bomReport(): HasOne
    {
        return $this->hasOne(BOM_Report::class, 'item_code', 'item_code');
    }
}
