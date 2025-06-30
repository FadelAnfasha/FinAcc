<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Packing extends Model
{
    use HasFactory;

    protected $table = 'packings';
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer

    protected $fillable = [
        'item_code',
        'price',
    ];

    public function boms()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
