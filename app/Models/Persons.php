<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persons extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
    'person_fname',
    'person_lastname',
    'person_corpname',
    'person_idnumber',
    'person_ruc',
    'person_birtdate',
    'person_photo',
    'person_address',
    'p_type_id',
    'country_id',
    'city_id'
];


    public function type()
    {
        return $this->belongsTo(PersonTypes::class, 'p_type_id');
    }
    public function productsProvider(){
        return $this->hasMany(ProductsProvider::class);
    }
    public function country()
    {
        return $this->belongsTo(Countries::class, 'country_id');
    }

    public function city()
    {
        return $this->belongsTo(Cities::class, 'city_id');
    }
    public function sales(){
        return $this->hasMany(Sales::class, 'person_id');
    }
}
