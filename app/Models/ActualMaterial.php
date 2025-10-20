<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActualMaterial extends Model
{
    protected $table = 'actual_materials'; // pastikan nama tabel sesuai dengan yang ada di database
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer
    protected $fillable = [
        'item_code',
        'jan_price',
        'jan_qty',
        'feb_price',
        'feb_qty',
        'mar_price',
        'mar_qty',
        'apr_price',
        'apr_qty',
        'may_price',
        'may_qty',
        'jun_price',
        'jun_qty',
        'jul_price',
        'jul_qty',
        'aug_price',
        'aug_qty',
        'sep_price',
        'sep_qty',
        'oct_price',
        'oct_qty',
        'nov_price',
        'nov_qty',
        'dec_price',
        'dec_qty',

    ];

    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
