<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','type', 'payment', 'status'];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'package_client');
    }
}