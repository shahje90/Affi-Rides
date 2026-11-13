<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\WalletTransactionStatus;
use App\Models\Ride;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualQaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_passenger_rider_admin_flow_completes_and_auto_offlines_low_wallet_rider(): void
    {
        Storage::fake('local');
        $admin = User::factory()->superAdmin()->create();

        $this->post(route('register.store'), [
            'name' => 'QA Passenger',
            'phone' => '03012345670',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])->assertRedirect(route('passenger.home'));
        $passenger = User::query()->where('phone', '03012345670')->firstOrFail();
        $this->assertAuthenticatedAs($passenger);
        $this->post(route('logout'));

        $this->post(route('rider.register.store'), [
            'name' => 'QA Rider',
            'phone' => '03012345671',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'cnic_number' => '3520212345671',
            'bike_number' => 'ATK-QA-1',
            'bike_model' => 'Honda CD 70',
            'cnic_front_image' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'cnic_back_image' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            'bike_document_image' => UploadedFile::fake()->create('bike.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('rider.kyc-status'));
        $rider = User::query()->where('phone', '03012345671')->firstOrFail();
        $profile = $rider->riderProfile()->firstOrFail();
        $this->assertSame(KycStatus::Pending, $profile->kyc_status);
        Storage::disk('local')->assertExists($profile->cnic_front_image);

        $this->actingAs($admin)->post(route('admin.kyc.review', $profile), [
            'kyc_status' => KycStatus::Approved->value,
            'kyc_note' => 'Documents reviewed.',
        ])->assertRedirect(route('admin.kyc.show', $profile));

        $this->actingAs($rider)->post(route('rider.wallet.recharge'), [
            'amount' => '105.00',
            'trx_id' => 'QA-RECHARGE-105',
        ])->assertRedirect();
        $deposit = WalletTransaction::query()->where('trx_id', 'QA-RECHARGE-105')->firstOrFail();

        $this->actingAs($admin)->getJson(route('admin.pending-items'))
            ->assertOk()
            ->assertJsonPath('deposits.0.id', $deposit->id);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Enable Alarm Sound');
        $this->post(route('admin.wallet.approve', $deposit), [
            'admin_note' => 'QA payment verified.',
        ])->assertRedirect();
        $this->assertSame(WalletTransactionStatus::Approved, $deposit->fresh()->status);
        $this->assertSame('105.00', $profile->fresh()->wallet_balance);

        $this->actingAs($rider)->post(route('rider.online.toggle'))->assertRedirect();
        $this->assertTrue($profile->fresh()->is_online);
        $this->postJson(route('rider.location'), ['lat' => 33.7667, 'lng' => 72.3592])
            ->assertOk()
            ->assertJsonPath('updated', true);

        $this->actingAs($passenger)->post(route('passenger.rides.store'), [
            'pickup_address' => 'Attock City',
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
            'dropoff_address' => 'Nearby pickup',
            'dropoff_lat' => '33.76670000',
            'dropoff_lng' => '72.35920000',
        ])->assertSessionHasNoErrors();
        $ride = Ride::query()->where('passenger_id', $passenger->id)->firstOrFail();
        $this->assertSame('80.00', $ride->estimated_fare);

        $this->actingAs($rider)->getJson(route('rider.ride-requests'))
            ->assertOk()
            ->assertJsonPath('requests.0.id', $ride->id);
        $this->postJson(route('rider.rides.accept', $ride))->assertOk();
        $this->actingAs($passenger)->getJson(route('passenger.rides.status', $ride))
            ->assertOk()
            ->assertJsonPath('status', RideStatus::Accepted->value)
            ->assertJsonPath('rider.phone', $rider->phone);

        foreach ([RideStatus::Arrived, RideStatus::InProgress, RideStatus::Completed] as $status) {
            $this->actingAs($rider)->post(route('rider.rides.status', $ride), [
                'status' => $status->value,
            ])->assertRedirect();
        }

        $ride->refresh();
        $profile->refresh();
        $this->assertSame(RideStatus::Completed, $ride->status);
        $this->assertSame('12.00', $ride->company_commission);
        $this->assertSame('68.00', $ride->rider_earning);
        $this->assertSame('93.00', $profile->wallet_balance);
        $this->assertFalse($profile->is_online);
        $this->assertDatabaseHas('wallet_transactions', [
            'rider_id' => $rider->id,
            'ride_id' => $ride->id,
            'type' => 'commission_deduction',
            'amount' => '-12.00',
            'balance_after' => '93.00',
        ]);
    }
}
