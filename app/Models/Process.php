<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Process extends Model
{
    use HasFactory;

    protected $table = 'process';
    protected $primaryKey = 'item_code';
    public $incrementing = false; // jika item_no bukan auto-increment
    protected $keyType = 'string'; // jika item_no bukan integer

    protected $fillable = [
        'item_code',
        'description',
        'price',
        'manufacturer'
    ];

    public function boms()
    {
        return $this->hasOne(BillOfMaterial::class, 'item_code', 'item_code');
    }
}
