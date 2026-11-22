<?php

namespace App\Models;

use App\Enums\KycStatus;
use Database\Factories\RiderProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderProfile extends Model
{
    /** @use HasFactory<RiderProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cnic_number',
        'cnic_front_image',
        'cnic_back_image',
        'bike_number',
        'bike_model',
        'bike_document_image',
        'kyc_status',
        'kyc_note',
        'is_online',
        'current_lat',
        'current_lng',
        'last_location_updated_at',
        'wallet_balance',
    ];

    protected function casts(): array
    {
        return [
            'kyc_status' => KycStatus::class,
            'is_online' => 'boolean',
            'current_lat' => 'decimal:8',
            'current_lng' => 'decimal:8',
            'last_location_updated_at' => 'datetime',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('kyc_status', KycStatus::Approved->value);
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $km): Builder
    {
        [$distance, $bindings] = self::nearbyDistanceExpression('current_lat', 'current_lng', $lat, $lng);

        return $query
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereRaw("{$distance} <= (0 + ?)", [...$bindings, $km]);
    }

    /**
     * @return array{0: string, 1: array<int, float>}
     */
    public static function nearbyDistanceExpression(
        string $latitudeColumn,
        string $longitudeColumn,
        float $lat,
        float $lng,
    ): array {
        $cosine = "cos(radians(?)) * cos(radians({$latitudeColumn})) * cos(radians({$longitudeColumn}) - radians(?)) + sin(radians(?)) * sin(radians({$latitudeColumn}))";
        $bindings = [];

        for ($i = 0; $i < 3; $i++) {
            array_push($bindings, $lat, $lng, $lat);
        }

        return [
            "(6371 * acos(CASE WHEN {$cosine} > 1 THEN 1 WHEN {$cosine} < -1 THEN -1 ELSE {$cosine} END))",
            $bindings,
        ];
    }
}
