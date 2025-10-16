<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchasesDetails extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'pd_desc',
        'pd_qty',
        'pd_amount'
    ];

    public function purchase(){
        return $this->belongsTo(Purchases::class, 'purchase_id', 'id');
    }
    public function product(){
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }
}
