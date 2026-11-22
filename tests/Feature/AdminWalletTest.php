<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\RiderProfile;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_a_deposit_once_and_credits_the_wallet_atomically(): void
    {
        [$admin, $rider, $profile] = $this->accounts();
        $deposit = WalletTransaction::factory()->create([
            'rider_id' => $rider->id,
            'amount' => '250.50',
            'trx_id' => 'TID-ONCE-1',
        ]);

        $this->actingAs($admin)->post(route('admin.wallet.approve', $deposit), [
            'admin_note' => 'Payment verified.',
        ])->assertRedirect();

        $this->assertSame('250.50', $profile->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $deposit->id,
            'status' => WalletTransactionStatus::Approved->value,
            'balance_after' => '250.50',
            'reviewed_by' => $admin->id,
            'admin_note' => 'Payment verified.',
        ]);

        $this->from(route('admin.dashboard'))->actingAs($admin)
            ->post(route('admin.wallet.approve', $deposit), ['admin_note' => 'Repeated review.'])
            ->assertSessionHasErrors('wallet_transaction');

        $this->assertSame('250.50', $profile->fresh()->wallet_balance);
    }

    public function test_admin_rejects_a_deposit_without_crediting_the_rider(): void
    {
        [$admin, $rider, $profile] = $this->accounts();
        $deposit = WalletTransaction::factory()->create(['rider_id' => $rider->id, 'trx_id' => 'TID-REJECT-1']);

        $this->actingAs($admin)->post(route('admin.wallet.reject', $deposit), [
            'admin_note' => 'Transaction could not be verified.',
        ])->assertRedirect();

        $this->assertSame('0.00', $profile->fresh()->wallet_balance);
        $this->assertSame(WalletTransactionStatus::Rejected, $deposit->fresh()->status);
    }

    public function test_duplicate_recharge_tid_is_rejected(): void
    {
        $rider = User::factory()->rider()->create();
        RiderProfile::factory()->approved()->create(['user_id' => $rider->id]);
        WalletTransaction::factory()->create(['rider_id' => $rider->id, 'trx_id' => 'DUPLICATE-TID']);

        $this->actingAs($rider)->post(route('rider.wallet.recharge'), [
            'amount' => '100.00',
            'trx_id' => 'DUPLICATE-TID',
        ])->assertSessionHasErrors('trx_id');
    }

    public function test_admin_manual_adjustment_requires_a_note_and_updates_the_ledger(): void
    {
        [$admin, $rider, $profile] = $this->accounts();

        $this->actingAs($admin)->post(route('admin.wallet.adjustments.store'), [
            'rider_id' => $rider->id,
            'amount' => '-25.25',
        ])->assertSessionHasErrors('admin_note');

        $this->actingAs($admin)->post(route('admin.wallet.adjustments.store'), [
            'rider_id' => $rider->id,
            'amount' => '125.25',
            'admin_note' => 'Verified manual top-up.',
        ])->assertRedirect();

        $this->assertSame('125.25', $profile->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'rider_id' => $rider->id,
            'type' => WalletTransactionType::Adjustment->value,
            'amount' => '125.25',
            'admin_note' => 'Verified manual top-up.',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_adjust_a_wallet_below_zero(): void
    {
        [$admin, $rider, $profile] = $this->accounts();

        $this->from(route('admin.dashboard'))
            ->actingAs($admin)
            ->post(route('admin.wallet.adjustments.store'), [
                'rider_id' => $rider->id,
                'amount' => '-0.01',
                'admin_note' => 'Invalid underflow.',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('0.00', $profile->fresh()->wallet_balance);
        $this->assertDatabaseMissing('wallet_transactions', [
            'rider_id' => $rider->id,
            'type' => WalletTransactionType::Adjustment->value,
        ]);
    }

    public function test_admin_can_make_a_negative_adjustment_without_underflow(): void
    {
        [$admin, $rider, $profile] = $this->accounts();
        $profile->update(['wallet_balance' => '100.00']);

        $this->actingAs($admin)->post(route('admin.wallet.adjustments.store'), [
            'rider_id' => $rider->id,
            'amount' => '-25.25',
            'admin_note' => 'Verified deduction.',
        ])->assertRedirect();

        $this->assertSame('74.75', $profile->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'rider_id' => $rider->id,
            'type' => WalletTransactionType::Adjustment->value,
            'amount' => '-25.25',
            'balance_after' => '74.75',
            'status' => WalletTransactionStatus::Approved->value,
        ]);
    }

    public function test_pending_alarm_feed_is_admin_only_and_includes_pending_riders(): void
    {
        [$admin, $rider] = $this->accounts();
        $deposit = WalletTransaction::factory()->create(['rider_id' => $rider->id, 'trx_id' => 'TID-ALARM']);
        RiderProfile::query()->where('user_id', $rider->id)->update(['kyc_status' => KycStatus::Pending]);

        $this->actingAs($admin)->getJson(route('admin.pending-items'))
            ->assertOk()
            ->assertJsonPath('deposits.0.id', $deposit->id)
            ->assertJsonPath('deposits.0.rider_name', $rider->name)
            ->assertJsonPath('kyc.0.rider_name', $rider->name);

        $this->actingAs($rider)->getJson(route('admin.pending-items'))->assertForbidden();
    }

    private function accounts(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $rider = User::factory()->rider()->create();
        $profile = RiderProfile::factory()->create([
            'user_id' => $rider->id,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '0.00',
        ]);

        return [$admin, $rider, $profile];
    }
}
