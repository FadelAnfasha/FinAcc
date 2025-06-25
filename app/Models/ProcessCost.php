<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessCost extends Model
{
    protected $table = 'process_costs';
    protected $primaryKey = 'item_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_code',
        'max_of_disc',
        'max_of_rim',
        'max_of_sidering',
        'max_of_assy',
        'max_of_ced',
        'max_of_topcoat',
        'max_of_packaging',
        'max_of_total',
    ];

    public function bom()
    {
        return $this->hasOne(BOM::class, 'item_code', 'item_code');
    }
}
