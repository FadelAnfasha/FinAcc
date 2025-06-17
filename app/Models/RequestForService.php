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
        'priority',
        'input_date',
        'description',
        'status',
        'attachment',
    ];
}
