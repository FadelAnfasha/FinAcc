<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActualSalesQuantity extends Model
{
    protected $table = 'actual_salesquantities'; // pastikan nama tabel sesuai dengan yang ada di database
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer
    protected $fillable = [
        'item_code',
        'jan_qty',
        'feb_qty',
        'mar_qty',
        'apr_qty',
        'may_qty',
        'jun_qty',
        'jul_qty',
        'aug_qty',
        'sep_qty',
        'oct_qty',
        'nov_qty',
        'dec_qty',
    ];
    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
