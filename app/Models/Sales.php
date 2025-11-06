<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SalesDetails;
use App\Models\Persons;
use App\Models\TillDetails;

class Sales extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'person_id',
        'sale_date',
        'sale_number',
        'sale_status',
        'sale_type'
        ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function person()
    {
        return $this->belongsTo(Persons::class, 'person_id', 'id');
    }

    public function sales_details()
    {
        return $this->hasMany(SalesDetails::class, 'sale_id', 'id');
    }

    public function tills_details()
    {
        return $this->hasMany(TillDetails::class, 'ref_id', 'id');
    }

}
