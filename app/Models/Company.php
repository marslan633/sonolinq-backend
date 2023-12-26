<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', '_token', '_method'];


    public function type_of_services()
    {
        return $this->belongsToMany(Service::class);
    }  


    public function client() {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}