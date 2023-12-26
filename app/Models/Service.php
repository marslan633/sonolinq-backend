<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'price',
        'status',
        'category_id',
    ];

    public function category()
    {
        return $this->hasOne(ServiceCategory::class, 'id', 'category_id');
    }
}