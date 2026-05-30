<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'container_id',
        'location',
        'timestamp',
        'description'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Relasi Many-to-One dengan Container
     * Banyak tracking logs milik satu container
     */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }
}
