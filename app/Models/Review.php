<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'rating',
        'feedback',
        'type'
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}