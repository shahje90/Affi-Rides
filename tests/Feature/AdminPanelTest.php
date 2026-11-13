<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\UserStatus;
use App\Models\Ride;
use App\Models\RiderProfile;
use App\Models\Setting as SettingModel;
use App\Models\User;
use App\Services\FareCalculator;
use App\Support\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_pages_and_live_snapshot_are_available_to_admins(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);

        foreach ([
            'admin.dashboard',
            'admin.kyc.index',
            'admin.users.index',
            'admin.monitor',
            'admin.rides.index',
            'admin.settings.index',
            'admin.reports.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->getJson(route('admin.monitor.snapshot'))
            ->assertOk()
            ->assertJsonStructure(['riders', 'rides']);
    }

    public function test_admin_can_review_kyc_and_only_admins_can_open_private_documents(): void
    {
        Storage::fake('local');
        $admin = User::factory()->superAdmin()->create();
        $rider = User::factory()->rider()->create();
        $profile = RiderProfile::factory()->create([
            'user_id' => $rider->id,
            'cnic_front_image' => 'kyc/front.png',
        ]);
        Storage::disk('local')->put('kyc/front.png', 'private-document');

        $this->actingAs($rider)->get(route('admin.kyc.document', [$profile, 'cnic-front']))
            ->assertForbidden();

        $documentResponse = $this->actingAs($admin)->get(route('admin.kyc.document', [$profile, 'cnic-front']));
        $documentResponse->assertOk();
        $this->assertSame('private-document', $documentResponse->baseResponse->getFile()->getContent());

        $this->actingAs($admin)->post(route('admin.kyc.review', $profile), [
            'kyc_status' => KycStatus::Approved->value,
            'kyc_note' => 'Documents verified.',
        ])->assertRedirect(route('admin.kyc.show', $profile));

        $this->assertSame(KycStatus::Approved, $profile->fresh()->kyc_status);
        $this->assertSame('Documents verified.', $profile->fresh()->kyc_note);
    }

    public function test_suspending_a_rider_also_takes_them_offline(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $rider = User::factory()->rider()->create();
        RiderProfile::factory()->approved()->create([
            'user_id' => $rider->id,
            'is_online' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.users.status', $rider), [
            'status' => UserStatus::Suspended->value,
        ])->assertRedirect();

        $this->assertSame(UserStatus::Suspended, $rider->fresh()->status);
        $this->assertFalse($rider->riderProfile->fresh()->is_online);
    }

    public function test_settings_update_invalidates_cached_fare_values_and_stores_qr_image(): void
    {
        Storage::fake('public');
        $this->seed(SettingsSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $this->assertSame('60.00', Setting::get('base_fare'));

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'base_fare' => '200.00',
            'per_km_rate' => '30.00',
            'minimum_fare' => '80.00',
            'commission_percentage' => '15.00',
            'minimum_rider_wallet_limit' => '100.00',
            'search_radius_km' => '5.00',
            'road_distance_factor' => '1.30',
            'idle_poll_seconds' => '45',
            'active_poll_seconds' => '10',
            'min_move_meters' => '30',
            'ride_request_timeout_seconds' => '60',
            'fare_rounding_increment' => '5',
            'payment_qr_code_image' => UploadedFile::fake()->createWithContent(
                'payment.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/rWQAAAAASUVORK5CYII='),
            ),
        ])->assertRedirect();

        $this->assertSame('200.00', Setting::get('base_fare'));
        $this->assertSame('200.00', app(FareCalculator::class)->estimate(33.7, 72.3, 33.7, 72.3)['estimated_fare']);
        $qrPath = SettingModel::query()->where('key', 'payment_qr_code_image')->value('value');
        $this->assertNotEmpty($qrPath);
        Storage::disk('public')->assertExists($qrPath);
    }

    public function test_ride_filters_and_csv_export_use_the_selected_date_range(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $passenger = User::factory()->create();
        $rider = User::factory()->rider()->create();
        $ride = Ride::factory()->create([
            'passenger_id' => $passenger->id,
            'rider_id' => $rider->id,
            'status' => RideStatus::Completed,
            'company_commission' => '45.00',
            'rider_earning' => '255.00',
            'final_fare' => '300.00',
            'completed_at' => now(),
        ]);
        Ride::factory()->create([
            'passenger_id' => $passenger->id,
            'status' => RideStatus::Cancelled,
            'created_at' => now()->subDays(10),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.rides.index', ['status' => 'completed', 'rider_id' => $rider->id]))
            ->assertOk()
            ->assertSee("#{$ride->id}");

        $response = $this->get(route('admin.reports.export', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('45.00', $response->streamedContent());
    }
}
