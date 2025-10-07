<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProofPayments extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = ['proof_payment_desc', 'payment_type_id'];

    public function paymentType()
    {
        return $this->belongsTo(PaymentTypes::class, 'payment_type_id', 'id');
    }
    public function tillDetailProofPayments()
    {
        return $this->hasMany(TillDetailProofPayments::class, 'id', 'proof_payment_id');
    }
}