<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

        protected $fillable = [
        'user_id',
        'name',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

}