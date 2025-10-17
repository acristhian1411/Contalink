<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\SoftDeletes;


class RefundDetails extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'refund_id',
        'product_id',
        'quantity',
    ];

    public function refund()
    {
        return $this->belongsTo(Refunds::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
