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
        'price'
    ];

    public function products()
    {
        return $this->hasMany(Service::class);
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class);
    }


    // for checking
    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}