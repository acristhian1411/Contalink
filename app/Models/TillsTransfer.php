<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
class TillsTransfer extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'origin_id',
        'destiny_id',
        't_transfer_amount',
        't_transfer_date',
        't_transfer_obs', 
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
    ];

    public function origin()
    {
        return $this->belongsTo(Tills::class, 'till_id', 'origin_id');
    }
    public function destiny()
    {
        return $this->belongsTo(Tills::class, 'till_id', 'destiny_id');
    }
}
