<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class RequestForService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'npk',
        'priority_id',
        'input_date',
        'description',
        'status_id',
        'attachment',
    ];
    protected $casts = [
        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
    ];

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }
}
