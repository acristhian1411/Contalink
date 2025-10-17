<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;


class Refunds extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'sale_id',
        'refund_date',
        'refund_obs',
        'refund_status',
        ];

    public function sale()
    {
        return $this->belongsTo(Sales::class);
    }

    public function refundDetails()
    {
        return $this->hasMany(RefundDetails::class, 'refund_id', 'id');
    }
}
