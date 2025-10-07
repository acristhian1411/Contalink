<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentTypes extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;
    protected $fillable = ['payment_type_desc'];
    protected $with = ['proofPayments'];

    protected function casts(): array
    {
        return [
            'proofPayments' => 'array',
        ];
    }
    public function toArray()
    {
        $array = parent::toArray();
        // Convertir todas las claves a camelCase
        return collect($array)->mapWithKeys(function ($value, $key) {
            return [Str::camel($key) => $value];
        })->toArray();
    }
    public function proofPayments()
    {
        return $this->hasMany(ProofPayments::class, 'payment_type_id', 'id');
    }

}
