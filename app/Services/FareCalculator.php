<?php

namespace App\Services;

use App\Support\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class FareCalculator
{
    /**
     * @return array{distance_km: string, estimated_fare: string}
     */
    public function estimate(float $pickupLat, float $pickupLng, float $dropoffLat, float $dropoffLng): array
    {
        $roadFactor = (float) Setting::get('road_distance_factor', '1.3');
        $distanceKm = BigDecimal::of(number_format(
            $this->haversineKilometers($pickupLat, $pickupLng, $dropoffLat, $dropoffLng) * $roadFactor,
            8,
            '.',
            '',
        ))->toScale(2, RoundingMode::HalfUp);

        $fare = BigDecimal::of((string) Setting::get('base_fare', '60.00'))
            ->plus($distanceKm->multipliedBy(BigDecimal::of((string) Setting::get('per_km_rate', '30.00'))));
        $minimumFare = BigDecimal::of((string) Setting::get('minimum_fare', '80.00'));

        if ($fare->isLessThan($minimumFare)) {
            $fare = $minimumFare;
        }

        $increment = BigDecimal::of((string) Setting::get('fare_rounding_increment', '5'));

        if ($increment->isLessThanOrEqualTo(BigDecimal::zero())) {
            throw new \InvalidArgumentException('Fare rounding increment must be greater than zero.');
        }

        $roundedFare = $fare
            ->dividedBy($increment, 0, RoundingMode::HalfUp)
            ->multipliedBy($increment)
            ->toScale(2, RoundingMode::HalfUp);

        return [
            'distance_km' => (string) $distanceKm,
            'estimated_fare' => (string) $roundedFare,
        ];
    }

    public function haversineKilometers(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lng2 - $lng1);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
