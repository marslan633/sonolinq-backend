<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'sonographer_id',
        // 'service_category_id',
        // 'service_id',
        // 'type',
        // 'date',
        // 'time',
    ];

    public function preferences()
    {
        return $this->hasOne(Preference::class, 'booking_id', 'id');
    }

    public function doctor() {
        return $this->hasOne(Client::class, 'id', 'doctor_id');
    }

    public function sonographer() {
        return $this->hasOne(Client::class, 'id', 'sonographer_id');
    }

    // public function service_category() {
    //     return $this->hasOne(ServiceCategory::class, 'id', 'service_category_id');
    // }

    // public function service() {
    //     return $this->hasOne(Service::class, 'id', 'service_id');
    // }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}