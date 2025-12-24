<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpParser\PrettyPrinter\Standard;

class StandardConsumable extends Model
{
    use HasFactory;

    protected $table = 'standard_consumables';
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer

    protected $fillable = [
        'item_code',
        'price',
    ];

    public function bom()
    {
        return $this->hasOne(StandardBillOfMaterial::class, 'item_code', 'item_code');
    }
}
