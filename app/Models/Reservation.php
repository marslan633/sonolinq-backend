<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'type', 'date', 'time', 'amount'];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function serviceCategories()
    {
        return $this->belongsToMany(ServiceCategory::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }
}