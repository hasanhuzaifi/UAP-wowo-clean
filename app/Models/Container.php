<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Container extends Model
{
    use HasFactory;

    protected $fillable = [
        'container_id',
        'waste_type',
        'weight_kg',
        'status'
    ];

    protected $casts = [
        'weight_kg' => 'float',
    ];

    /**
     * Relasi One-to-Many dengan TrackingLog
     * Satu container dapat memiliki banyak tracking logs
     */
    public function trackingLogs(): HasMany
    {
        return $this->hasMany(TrackingLog::class);
    }
}
