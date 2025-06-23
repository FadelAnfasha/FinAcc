<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessPartner extends Model
{
    protected $table = 'business_partner';
    protected $primaryKey = 'bp_code';
    public $incrementing = false; // Karena bp_code bukan integer auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'bp_code',
        'bp_name', // tambahkan kolom lain yang relevan
    ];

    // public function salesQty()
    // {
    //     return $this->hasMany(RawSalesQty::class, 'bp_code', 'bp_code');
    // }
}
