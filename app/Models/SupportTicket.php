<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'booking_id',
        'title',
        'type',
        'comment',
        'status',
        'ticket_tracking_id'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function ticket_notes()
    {
        return $this->hasMany(TicketNote::class, 'ticket_id');
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }
}