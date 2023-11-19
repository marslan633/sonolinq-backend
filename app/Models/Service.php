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

    public function product_variant() {
        return $this->hasMany(ProductVariant::class);
    }

    // Define the inverse of the relationship
    public function deals()
    {
        return $this->belongsToMany(Deal::class);
    }
}