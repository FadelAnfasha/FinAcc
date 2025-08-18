<?php

namespace App\Models;

use Faker\Provider\Biased;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StandardMaterial extends Model
{
    use HasFactory;

    protected $table = 'standard_materials'; // pastikan nama tabel sesuai dengan yang ada di database
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer
    protected $fillable = [
        'item_code',
        'in_stock',
        'item_group',
        'price',
        'manufacturer',
    ];

    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }

    // public function fixed()
    // {
    //     return $this->hasOne(fixedMaterial::class, 'item_code', 'item_code');
    // }

    // public function standard()
    // {
    //     return $this->hasOne(standardMaterial::class, 'item_code', 'item_code');
    // }
}
