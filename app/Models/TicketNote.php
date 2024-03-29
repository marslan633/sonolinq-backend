<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'ticket_id',    
        'note',
        'type'
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }
}