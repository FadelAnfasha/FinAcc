<?php

namespace App\Models;

use Faker\Provider\Biased;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials'; // pastikan nama tabel sesuai dengan yang ada di database
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer

    protected $fillable = [
        'item_code',
        'in_stock',
        'item_group',
        'price',
        'date_of_update',
        'manufacturer',
    ];

    public function bom()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
