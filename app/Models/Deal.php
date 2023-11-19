<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_id',
        'name',
        'products_id',
        'price',
        'image'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }  
}