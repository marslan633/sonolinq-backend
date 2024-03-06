<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'rating_doctor',
        'feedback_doctor',
        'rating_sonographer',
        'feedback_sonographer'
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}