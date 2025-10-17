<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeasurementUnit extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'unit_name',
        'unit_abbreviation',
        'allows_decimals',
        'is_active'
    ];

    protected $casts = [
        'allows_decimals' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get all products that use this measurement unit
     */
    public function products()
    {
        return $this->hasMany(Products::class);
    }

    /**
     * Scope to get only active measurement units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this unit can be deleted (no products using it)
     */
    public function canBeDeleted()
    {
        return $this->products()->count() === 0;
    }
}