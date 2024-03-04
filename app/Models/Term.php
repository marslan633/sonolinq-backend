<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'heading', 'paragraph', 'status'];


    public function children()
    {
        return $this->hasMany(TermChild::class);
    }
}