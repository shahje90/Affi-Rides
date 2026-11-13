<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\FareCalculator;
use App\Support\Setting as CachedSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FareCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (['road_distance_factor', 'base_fare', 'per_km_rate', 'minimum_fare', 'fare_rounding_increment'] as $key) {
            CachedSetting::forget($key);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_calculates_haversine_distance_and_a_rounded_decimal_fare(): void
    {
        $this->setFareSettings();
        $calculator = new FareCalculator;

        $estimate = $calculator->estimate(0, 0, 0, 0.01);

        $this->assertSame('1.11', $estimate['distance_km']);
        $this->assertSame('95.00', $estimate['estimated_fare']);
        $this->assertEqualsWithDelta(111.19, $calculator->haversineKilometers(0, 0, 1, 0), 0.1);
    }

    #[Test]
    public function it_applies_the_minimum_fare_and_reads_updated_settings(): void
    {
        $this->setFareSettings();
        $calculator = new FareCalculator;
        $this->assertSame('80.00', $calculator->estimate(0, 0, 0, 0)['estimated_fare']);

        Setting::query()->updateOrCreate(['key' => 'base_fare'], ['value' => '100.00']);
        CachedSetting::forget('base_fare');

        $this->assertSame('100.00', $calculator->estimate(0, 0, 0, 0)['estimated_fare']);
    }

    private function setFareSettings(): void
    {
        foreach ([
            'road_distance_factor' => '1.0',
            'base_fare' => '60.00',
            'per_km_rate' => '30.00',
            'minimum_fare' => '80.00',
            'fare_rounding_increment' => '5',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            CachedSetting::forget($key);
        }
    }
}
