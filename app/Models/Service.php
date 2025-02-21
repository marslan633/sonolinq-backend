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
         'type',
        'category_id',
    ];

    public function category()
    {
        return $this->hasOne(ServiceCategory::class, 'id', 'category_id');
    }


    public function reservations()
    {
        return $this->belongsToMany(Reservation::class);
    }


    // for checking
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}
