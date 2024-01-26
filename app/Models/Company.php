<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', '_token', '_method'];
    
    protected $fillable = [
        'client_id',
        'company_name',
        'register_no',
        'is_vat',
        'reg_no_letter',
        'personal_director_id',
        'prove_of_address',
        'years_of_experience',
        'type_of_equipment',
        'equipment_availability',
        'pacs_reading',
        'practice_instructions',
        'references',
        'languages_spoken',
        'any_limitation',
        'certifications',
        'level',
        'facility_hours',
    ];


    public function type_of_services()
    {
        return $this->belongsToMany(Service::class);
    }  


    public function client() {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}