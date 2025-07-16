<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;



use Illuminate\Database\Eloquent\Model;

class Valve extends Model
{
    use HasFactory;

    protected $table = 'valves';
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
