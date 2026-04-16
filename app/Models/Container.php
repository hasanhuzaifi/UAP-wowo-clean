<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $fillable = ['container_id', 'waste_type', 'weight_kg', 'status', 'tracking_logs'];
    
    // Agar tracking_logs otomatis menjadi array saat dipanggil
    protected $casts = [
        'tracking_logs' => 'array',
    ];
}