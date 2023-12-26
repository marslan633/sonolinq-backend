<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleSonographer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sonographer_id',
        'booking_id',
        'status'
    ];


    public function booking()
    {
        return $this->hasOne(Booking::class, 'id', 'booking_id');
    }
}