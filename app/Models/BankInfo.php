<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankInfo extends Model
{
    use HasFactory;
    
    protected $fillable = ['client_id','name', 'bank', 'branch_address', 'iban', 'swift_code', 'routing_number', 'status', 'priority'];


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}