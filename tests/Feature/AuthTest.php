<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_passenger_can_register_with_a_phone_number(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Passenger',
            'phone' => '03012345678',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ]);

        $response->assertRedirect(route('passenger.home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '03012345678',
            'role' => UserRole::Passenger->value,
            'status' => UserStatus::Active->value,
        ]);
        $this->assertTrue(Hash::check('secure-pass-123', User::query()->where('phone', '03012345678')->value('password')));
    }

    public function test_login_redirects_each_role_to_its_own_dashboard(): void
    {
        $destinations = [
            UserRole::SuperAdmin->value => 'admin.dashboard',
            UserRole::Rider->value => 'rider.dashboard',
            UserRole::Passenger->value => 'passenger.home',
        ];

        $accounts = [];

        foreach ($destinations as $role => $route) {
            $user = User::factory()->create([
                'role' => $role,
                'password' => 'secure-pass-123',
            ]);
            $accounts[] = [$route, $user];
        }

        foreach ($accounts as [$route, $user]) {
            $this->post(route('login.store'), [
                'phone' => $user->phone,
                'password' => 'secure-pass-123',
            ])->assertRedirect(route($route));
            $this->post(route('logout'));
        }
    }

    public function test_suspended_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'phone' => '03012345000',
            'password' => 'secure-pass-123',
            'status' => UserStatus::Suspended,
        ]);

        $this->post(route('login.store'), [
            'phone' => $user->phone,
            'password' => 'secure-pass-123',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_role_middleware_blocks_other_role_dashboards(): void
    {
        $routes = [
            UserRole::SuperAdmin->value => route('admin.dashboard'),
            UserRole::Rider->value => route('rider.dashboard'),
            UserRole::Passenger->value => route('passenger.home'),
        ];

        foreach ($routes as $role => $route) {
            $user = User::factory()->create(['role' => $role]);

            if ($role === UserRole::Rider->value) {
                RiderProfile::factory()->create(['user_id' => $user->id]);
            }

            foreach ($routes as $requiredRole => $requiredRoute) {
                $response = $this->actingAs($user)->get($requiredRoute);
                $response->assertStatus($role === $requiredRole ? 200 : 403);
            }
        }
    }
}
