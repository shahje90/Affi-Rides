<?php

namespace App\Models;

use App\Enums\RideStatus;
use Database\Factories\RideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    /** @use HasFactory<RideFactory> */
    use HasFactory;

    protected $fillable = [
        'passenger_id',
        'rider_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'distance_km',
        'estimated_fare',
        'final_fare',
        'company_commission',
        'rider_earning',
        'status',
        'cancelled_by',
        'cancel_reason',
        'accepted_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'dropoff_lat' => 'decimal:8',
            'dropoff_lng' => 'decimal:8',
            'distance_km' => 'decimal:2',
            'estimated_fare' => 'decimal:2',
            'final_fare' => 'decimal:2',
            'company_commission' => 'decimal:2',
            'rider_earning' => 'decimal:2',
            'status' => RideStatus::class,
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
