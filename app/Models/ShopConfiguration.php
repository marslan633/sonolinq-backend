<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'logo',
        'banner',
        'trade_line',
        'phone_number',
        'address',
        'working_hours'
    ];
}