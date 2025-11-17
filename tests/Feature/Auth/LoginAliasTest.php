<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAliasTest extends TestCase
{
    public function test_login_accepts_username_field_from_frontend(): void
    {
        $role = UserRole::firstOrCreate(
            ['role_name' => UserRole::BPS_ADMIN],
            ['display_name' => 'BPS Admin']
        );
        $user = User::factory()->create([
            'username' => 'bps_admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'bps_admin',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.username', 'bps_admin');
    }

    public function test_login_accepts_login_field_as_email_or_username(): void
    {
        $role = UserRole::firstOrCreate(
            ['role_name' => UserRole::BPS_ADMIN],
            ['display_name' => 'BPS Admin']
        );
        $user = User::factory()->create([
            'username' => 'officer1',
            'email' => 'officer@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'officer@example.test',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'officer@example.test');
    }
}
