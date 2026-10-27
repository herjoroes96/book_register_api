<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_can_log_in_and_receive_a_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'demo@example.com')
            ->assertJsonMissingPath('data.user.password');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_wrong_password_and_unknown_email_return_the_same_generic_error(): void
    {
        $this->seed();

        $wrongPassword = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'WrongPassword!',
        ]);

        $unknownEmail = $this->postJson('/api/v1/login', [
            'email' => 'missing@example.com',
            'password' => 'Password123!',
        ]);

        $wrongPassword
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Invalid credentials.']);

        $unknownEmail
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Invalid credentials.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_response_does_not_expose_password(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonMissing(['password'])
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ])->assertOk()->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('data.name', 'Demo User')
            ->assertJsonPath('data.email', 'demo@example.com')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.token');
    }

    public function test_profile_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);

        $this->withToken('invalid-token')
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_log_out_and_revoke_current_token(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Logged out.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_logout_keeps_other_active_tokens_valid(): void
    {
        $this->seed();

        $firstToken = $this->loginToken();
        $secondToken = $this->loginToken();

        $this->withToken($firstToken)->postJson('/api/v1/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        Auth::forgetGuards();

        $this->withToken($firstToken)->getJson('/api/v1/me')->assertStatus(401);

        Auth::forgetGuards();

        $this->withToken($secondToken)->getJson('/api/v1/me')->assertOk();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->travel(8)->days();

        try {
            Auth::forgetGuards();

            $this->withToken($token)
                ->getJson('/api/v1/me')
                ->assertStatus(401)
                ->assertHeader('Content-Type', 'application/json')
                ->assertExactJson(['message' => 'Unauthenticated.'])
                ->assertJsonMissingPath('data');
        } finally {
            $this->travelBack();
        }
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => 'throttle@example.com',
                'password' => 'WrongPassword!',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'throttle@example.com',
            'password' => 'WrongPassword!',
        ])
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '5')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_public_registration_route_does_not_exist(): void
    {
        $this->postJson('/api/v1/register')->assertNotFound();
    }

    public function test_auth_responses_do_not_expose_password_material(): void
    {
        $this->seed();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ])->assertOk();

        $login->assertJsonMissingPath('data.user.password');

        $token = (string) $login->json('data.token');

        $profile = $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk();

        $profile->assertJsonMissingPath('data.password');

        $logout = $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $logout->assertJsonMissing(['password']);
    }

    public function test_api_errors_are_json_without_accept_header(): void
    {
        $this->get('/api/v1/me')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->post('/api/v1/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_throttle_is_json_without_accept_header(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/api/v1/login', [
                'email' => 'json-throttle@example.com',
                'password' => 'WrongPassword!',
            ])->assertStatus(401);
        }

        $this->post('/api/v1/login', [
            'email' => 'json-throttle@example.com',
            'password' => 'WrongPassword!',
        ])
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '5')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);
    }

    public function test_demo_seeder_is_skipped_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        try {
            app(DemoUserSeeder::class)->run();

            $this->assertDatabaseCount('users', 0);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_auth_flow_does_not_log_credentials_or_tokens(): void
    {
        $this->seed();
        Log::spy();

        $token = $this->loginToken();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
            Log::shouldNotHaveReceived($level);
        }
    }

    private function loginToken(): string
    {
        return (string) $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'Password123!',
        ])->assertOk()->json('data.token');
    }
}
