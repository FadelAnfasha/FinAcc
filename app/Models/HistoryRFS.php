<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoryRFS extends Model
{
    use HasFactory;

    protected $table = 'histories_rfs';
    protected $fillable = [
        'rfs_id',
        'updated_by',
    ];

    public function rfs()
    {
        return $this->belongsTo(RequestForService::class, 'rfs_id', 'id');
    }
}
