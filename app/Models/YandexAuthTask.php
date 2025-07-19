<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class YandexAuthTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'status',
        'cookies',
        'error'
    ];

    protected $casts    = [
        'cookies' => 'array'
    ];
}
