<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentPaymentInfo extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'booking_id', 'p_intent_id', 'status', 'duration'];
}