<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WagesDistribution extends Model
{

    public $incrementing = false; // ID tidak auto increment
    protected $primaryKey = null; // tidak ada primary key

    protected $table = 'wages_distribution'; // pastikan nama tabel

    protected $fillable = [
        'blanking',
        'spinDisc',
        'autoDisc',
        'manualDisc',
        'discLathe',
        'rim1',
        'rim2',
        'rim3',
        'coiler',
        'forming',
        'assy1',
        'assy2',
        'machining',
        'shotPeening',
        'ced',
        'topcoat',
        'packing_dom',
        'packing_exp',
        'created_at',
        'updated_at',
    ];
}
