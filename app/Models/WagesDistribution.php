<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WagesDistribution extends Model
{

    public $incrementing = false;
    protected $primaryKey = null;

    protected $table = 'wages_distribution';

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
    ];
}
