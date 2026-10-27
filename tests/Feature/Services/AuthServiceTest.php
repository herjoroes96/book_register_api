<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_the_user_and_a_token(): void
    {
        $this->seed();

        $result = app(AuthService::class)->login([
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertIsArray($result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotEmpty($result['token']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_returns_null_for_invalid_credentials(): void
    {
        $this->seed();

        $result = app(AuthService::class)->login([
            'email' => 'demo@example.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_dummy_password_hash_configuration_is_valid(): void
    {
        $hash = config('auth.dummy_password_hash');

        $info = password_get_info($hash);

        $this->assertIsString($hash);
        $this->assertNotSame('', $hash);
        $this->assertSame('bcrypt', $info['algoName']);
        $this->assertSame((int) env('BCRYPT_ROUNDS', 12), (int) ($info['options']['cost'] ?? 0));
    }

    public function test_login_returns_null_for_an_unknown_email(): void
    {
        $result = app(AuthService::class)->login([
            'email' => 'missing@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_uses_the_configured_dummy_hash_for_unknown_emails(): void
    {
        $configuredHash = Hash::make('configured-dummy-password');
        config(['auth.dummy_password_hash' => $configuredHash]);

        Hash::spy();

        $result = app(AuthService::class)->login([
            'email' => 'missing@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertNull($result);
        Hash::shouldHaveReceived('check')
            ->once()
            ->with('Password123!', $configuredHash);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'demo@example.com')->firstOrFail();
        $firstToken = $user->createToken('api');
        $secondToken = $user->createToken('api');

        $user->withAccessToken($firstToken->accessToken);

        app(AuthService::class)->logout($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $firstToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $secondToken->accessToken->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_profile_returns_the_authenticated_user(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'demo@example.com')->firstOrFail();

        $this->assertSame($user, app(AuthService::class)->profile($user));
    }
}
