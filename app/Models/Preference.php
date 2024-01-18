<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'sonographer_level',
        'sonographer_gender',
        'sonographer_experience',
        'sonographer_registry',
        'sonographer_language',
    ];
}