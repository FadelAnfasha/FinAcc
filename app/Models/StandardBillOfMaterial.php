<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StandardBillOfMaterial extends Model
{
    use HasFactory;

    protected $table = 'standard_bill_of_materials';

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

    public function standardMaterial()
    {
        return $this->hasOne(StandardMaterial::class, 'item_code', 'item_code');
    }

    public function actualMaterial()
    {
        return $this->hasOne(ActualMaterial::class, 'item_code', 'item_code');
    }

    // public function processInfo()
    // {
    //     return $this->hasOne(Process::class, 'item_code', 'item_code');
    // }

    // public function packingInfo()
    // {
    //     return $this->hasOne(Packing::class, 'item_code', 'item_code');
    // }

    public function stdConsumableInfo()
    {
        return $this->hasOne(StandardConsumable::class, 'item_code', 'item_code');
    }

    public function processCost()
    {
        return $this->hasOne(ProcessCost::class, 'item_code', 'item_code');
    }

    public function SCReport(): HasOne
    {
        return $this->hasOne(StandardCost::class, 'item_code', 'item_code');
    }

    public function ACReport(): HasOne
    {
        return $this->hasOne(ActualCost::class, 'item_code', 'item_code');
    }
}
