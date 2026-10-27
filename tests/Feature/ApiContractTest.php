<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

final class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_errors_use_the_json_envelope(): void
    {
        $this->get('/api/v1/me')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.'])
            ->assertJsonMissingPath('data');
    }

    public function test_validation_errors_use_the_json_envelope(): void
    {
        $this->post('/api/v1/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');
    }

    public function test_not_found_errors_use_the_json_envelope(): void
    {
        $this->get('/api/v1/unknown-route')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Resource not found.'])
            ->assertJsonMissingPath('data');
    }

    public function test_method_not_allowed_errors_use_the_json_envelope(): void
    {
        $this->put('/api/v1/books')
            ->assertStatus(405)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Method not allowed.'])
            ->assertJsonMissingPath('data');
    }

    public function test_public_api_is_limited_per_ip(): void
    {
        $this->clearApiLimit('ip:127.0.0.1');

        for ($request = 0; $request < 60; $request++) {
            $this->get('/api/v1/health')->assertOk();
        }

        $this->get('/api/v1/health')
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);
    }

    public function test_authenticated_api_is_limited_per_user_without_mutation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $this->clearApiLimit('user:'.$user->id);
        $this->clearApiLimit('user:'.$otherUser->id);

        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/v1/me')->assertOk();
        }

        $this->getJson('/api/v1/me')
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);

        $this->postJson('/api/v1/books', [
            'name' => 'Should Not Be Created',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ])->assertStatus(429);

        $this->assertDatabaseCount('books', 0);

        Sanctum::actingAs($otherUser);

        $this->getJson('/api/v1/me')->assertOk();
    }

    public function test_unauthenticated_protected_and_unknown_requests_are_limited_per_ip(): void
    {
        $this->clearApiLimit('ip:127.0.0.1');

        for ($request = 0; $request < 60; $request++) {
            $this->get('/api/v1/me')->assertStatus(401);
        }

        $this->get('/api/v1/me')
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);

        $this->clearApiLimit('ip:127.0.0.1');

        for ($request = 0; $request < 60; $request++) {
            $this->get('/api/v1/unknown-route')->assertStatus(404);
        }

        $this->get('/api/v1/unknown-route')
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertExactJson(['message' => 'Too many requests.']);
    }

    private function clearApiLimit(string $key): void
    {
        // Laravel hashes named limiter keys with md5($name.$limitKey).
        RateLimiter::clear(md5('api'.$key));
        RateLimiter::clear('api:'.$key);
    }

    public function test_existing_success_and_pagination_shapes_remain_unchanged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/books', [
            'name' => 'Contract Compatibility',
            'isbn' => '0012345678901',
            'value' => '12.50',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'isbn', 'value', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.value', '12.50')
            ->assertJsonMissingPath('data.user_id');

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'isbn', 'value', 'created_at', 'updated_at']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.user_id');
    }

    public function test_unexpected_failures_return_a_safe_json_500_response(): void
    {
        $request = Request::create('/api/v1/books', 'GET');
        $response = app(ExceptionHandler::class)->render(
            $request,
            new RuntimeException('SENSITIVE_INTERNAL_DETAIL'),
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('{"message":"Server error."}', $response->getContent());
    }
}
