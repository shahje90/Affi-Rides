<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_registration_keeps_documents_on_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $response = $this->post(route('rider.register.store'), [
            'name' => 'New Rider',
            'phone' => '03012345678',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'cnic_number' => '3520212345671',
            'bike_number' => 'ATK-1234',
            'bike_model' => 'Honda CD 70',
            'cnic_front_image' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'cnic_back_image' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            'bike_document_image' => UploadedFile::fake()->create('bike.png', 100, 'image/png'),
        ]);

        $response->assertRedirect(route('rider.kyc-status'));
        $user = User::query()->where('phone', '03012345678')->firstOrFail();
        $profile = $user->riderProfile()->firstOrFail();

        $this->assertSame(UserRole::Rider, $user->role);
        $this->assertSame(KycStatus::Pending, $profile->kyc_status);
        foreach ([$profile->cnic_front_image, $profile->cnic_back_image, $profile->bike_document_image] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_rider_registration_rejects_non_image_kyc_files(): void
    {
        Storage::fake('local');

        $this->from(route('rider.register'))->post(route('rider.register.store'), [
            'name' => 'Invalid Rider',
            'phone' => '03012345679',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'cnic_number' => '3520212345671',
            'bike_number' => 'ATK-1234',
            'bike_model' => 'Honda CD 70',
            'cnic_front_image' => UploadedFile::fake()->create('front.pdf', 100, 'application/pdf'),
            'cnic_back_image' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            'bike_document_image' => UploadedFile::fake()->create('bike.png', 100, 'image/png'),
        ])->assertSessionHasErrors('cnic_front_image');

        $this->assertDatabaseMissing('users', ['phone' => '03012345679']);
    }

    public function test_rider_cannot_go_online_without_approved_kyc_and_wallet_minimum(): void
    {
        $user = User::factory()->rider()->create();
        $profile = RiderProfile::factory()->create(['user_id' => $user->id, 'kyc_status' => KycStatus::Pending]);

        $this->actingAs($user)->post(route('rider.online.toggle'))->assertForbidden();

        $profile->update(['kyc_status' => KycStatus::Approved, 'wallet_balance' => '99.99']);
        $this->actingAs($user)->post(route('rider.online.toggle'))->assertForbidden();
        $this->assertFalse($profile->fresh()->is_online);
    }

    public function test_online_rider_can_submit_a_pending_recharge_request(): void
    {
        $user = User::factory()->rider()->create();
        RiderProfile::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('rider.wallet.recharge'), [
            'amount' => '250.50',
            'trx_id' => 'TID-12345',
        ])->assertRedirect();

        $this->assertDatabaseHas('wallet_transactions', [
            'rider_id' => $user->id,
            'type' => WalletTransactionType::Deposit->value,
            'amount' => '250.50',
            'trx_id' => 'TID-12345',
            'status' => WalletTransactionStatus::Pending->value,
        ]);
    }
}
