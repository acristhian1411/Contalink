<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchases extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
    'person_id',
    'purchase_desc',
    'purchase_date',
    'purchase_number',
    'purchase_status',
    'purchase_type'
    ];

    public function person()
    {
        return $this->belongsTo(Persons::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchasesDetails::class, 'purchase_id', 'id');
    }

}
