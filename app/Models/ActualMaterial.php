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
        'price',
    ];
    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
