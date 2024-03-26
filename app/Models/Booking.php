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
        'charge_amount',
        'preference_id',
        // 'service_category_id',
        // 'service_id',
        // 'type',
        // 'date',
        // 'time',
        'doctor_comments',
        'sonographer_comments',
        'delivery_date',
        'complete_date',
        'booking_tracking_id',
        'cancellation_fee'
    ];

    public function preferences()
    {
        return $this->hasOne(Preference::class, 'id', 'preference_id');
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

    // public function reservations()
    // {
    //     return $this->hasMany(Reservation::class);
    // }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}