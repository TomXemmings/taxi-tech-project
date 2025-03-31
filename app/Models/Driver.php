<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'drivers';

    protected $fillable = ['name', 'phone', 'balance', 'car', 'active', 'surname'];

    protected $casts = [
        'car'    => 'array',
        'phone'  => 'string',
        'active' => DriverStatus::class
    ];

}
