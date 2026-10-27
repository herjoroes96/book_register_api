<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'name' => 'The Pragmatic Programmer',
            'isbn' => '0012345678901',
            'value' => 12.5,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'isbn', 'value', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.name', 'The Pragmatic Programmer')
            ->assertJsonPath('data.isbn', '0012345678901')
            ->assertJsonPath('data.value', '12.50')
            ->assertJsonMissingPath('data.user_id');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'name' => 'The Pragmatic Programmer',
            'isbn' => '0012345678901',
            'value' => '12.50',
        ]);
    }

    public function test_creation_requires_name_isbn_and_value(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'isbn', 'value']);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_creation_rejects_non_digit_isbn(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', [
            'name' => 'Invalid ISBN',
            'isbn' => '123-456',
            'value' => '10.00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_creation_rejects_invalid_value(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach ([-1, '12.345', '10000000000.00'] as $value) {
            $this->postJson('/api/v1/books', [
                'name' => 'Invalid Value',
                'isbn' => '9780135957059',
                'value' => $value,
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['value']);
        }

        $this->assertDatabaseCount('books', 0);
    }

    public function test_duplicate_isbn_is_allowed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'name' => 'Duplicate ISBN',
            'isbn' => '9780135957059',
            'value' => '49.90',
        ];

        $this->postJson('/api/v1/books', $payload)->assertCreated();
        $this->postJson('/api/v1/books', $payload)->assertCreated();

        $this->assertDatabaseCount('books', 2);
    }

    public function test_client_supplied_user_id_does_not_change_ownership(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/books', [
            'name' => 'Owned Book',
            'isbn' => '9780135957059',
            'value' => '49.90',
            'user_id' => $otherUser->id,
        ])->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'name' => 'Owned Book',
        ]);
        $this->assertDatabaseMissing('books', [
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_a_book(): void
    {
        $this->postJson('/api/v1/books', [
            'name' => 'The Pragmatic Programmer',
            'isbn' => '9780135957059',
            'value' => '49.90',
        ])->assertStatus(401);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_creation_response_does_not_expose_sensitive_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/books', [
            'name' => 'The Pragmatic Programmer',
            'isbn' => '9780135957059',
            'value' => '49.90',
        ])->assertCreated();

        $response
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissing(['password', 'token']);
    }

    public function test_authenticated_user_can_list_only_own_books_with_pagination(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Book::factory()->count(16)->for($user)->create();
        $otherBook = Book::factory()->for($otherUser)->create();

        Sanctum::actingAs($user);

        $firstPage = $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'isbn', 'value', 'created_at', 'updated_at']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonMissingPath('data.0.user_id');

        $firstPageIds = collect($firstPage->json('data'))->pluck('id');
        $this->assertNotContains($otherBook->id, $firstPageIds);

        $latestBook = Book::query()->where('user_id', $user->id)->orderByDesc('id')->first();
        $oldestBook = Book::query()->where('user_id', $user->id)->orderBy('id')->first();

        $this->assertSame($latestBook->id, $firstPage->json('data.0.id'));

        $secondPage = $this->getJson('/api/v1/books?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);

        $this->assertSame($oldestBook->id, $secondPage->json('data.0.id'));

        $this->getJson('/api/v1/books?page=99')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 16);
    }

    public function test_unauthenticated_user_cannot_list_books(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401);
    }

    public function test_authenticated_user_can_view_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'isbn', 'value', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.isbn', $book->isbn)
            ->assertJsonMissingPath('data.user_id');
    }

    public function test_book_detail_rejects_foreign_and_missing_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/books/{$foreignBook->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);

        $this->getJson('/api/v1/books/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);

        $this->get("/api/v1/books/{$foreignBook->id}.5")
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');

        $this->get('/api/v1/books/not-a-number')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_book_mutations_reject_malformed_identifiers(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create(['name' => 'Original Name']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$book->id}.5", ['name' => 'Hacked'])
            ->assertNotFound();

        $this->deleteJson("/api/v1/books/{$book->id}.5")
            ->assertNotFound();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_creation_accepts_boundary_values_and_rejects_exceeding_them(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', [
            'name' => str_repeat('a', 255),
            'isbn' => str_repeat('9', 20),
            'value' => '9999999999.99',
        ])->assertCreated();

        foreach ([
            ['name' => str_repeat('a', 256), 'isbn' => '1234567890', 'value' => '1.00'],
            ['name' => 'Valid Name', 'isbn' => str_repeat('9', 21), 'value' => '1.00'],
            ['name' => 'Valid Name', 'isbn' => '1234567890', 'value' => '10000000000.00'],
        ] as $payload) {
            $this->postJson('/api/v1/books', $payload)->assertStatus(422);
        }

        $this->assertDatabaseCount('books', 1);
    }

    public function test_unauthenticated_user_cannot_view_a_book(): void
    {
        $book = Book::factory()->create();

        $this->getJson("/api/v1/books/{$book->id}")->assertStatus(401);
    }

    public function test_authenticated_user_can_partially_update_a_book(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/books/{$book->id}", [
            'name' => 'Updated Name',
            'user_id' => $otherUser->id,
        ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'isbn', 'value', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.isbn', '9780135957059')
            ->assertJsonPath('data.value', '10.00')
            ->assertJsonMissingPath('data.user_id');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'name' => 'Updated Name',
            'isbn' => '9780135957059',
        ]);
    }

    public function test_update_changes_multiple_fields_and_preserves_leading_zero_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$book->id}", [
            'isbn' => '0012345678901',
            'value' => '54.90',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Original Name')
            ->assertJsonPath('data.isbn', '0012345678901')
            ->assertJsonPath('data.value', '54.90');
    }

    public function test_update_accepts_a_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        Book::factory()->for($user)->create(['isbn' => '1111111111111']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$book->id}", [
            'isbn' => '1111111111111',
        ])
            ->assertOk()
            ->assertJsonPath('data.isbn', '1111111111111');
    }

    public function test_update_requires_at_least_one_updatable_field(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($user)->create(['name' => 'Original Name']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$book->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->patchJson("/api/v1/books/{$book->id}", ['user_id' => $otherUser->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $book->refresh();
        $this->assertSame('Original Name', $book->name);
        $this->assertSame($user->id, $book->user_id);
    }

    public function test_update_rejects_invalid_fields_without_changing_the_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);
        Sanctum::actingAs($user);

        foreach ([
            ['name' => ''],
            ['isbn' => '12-34'],
            ['value' => '-1'],
        ] as $payload) {
            $this->patchJson("/api/v1/books/{$book->id}", $payload)->assertStatus(422);
        }

        $book->refresh();
        $this->assertSame('Original Name', $book->name);
        $this->assertSame('9780135957059', $book->isbn);
        $this->assertSame('10.00', $book->value);
    }

    public function test_update_accepts_boundary_values_and_rejects_exceeding_them(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);
        Sanctum::actingAs($user);

        $boundaryName = str_repeat('a', 255);
        $boundaryIsbn = str_repeat('9', 20);

        $this->patchJson("/api/v1/books/{$book->id}", [
            'name' => $boundaryName,
            'isbn' => $boundaryIsbn,
            'value' => '9999999999.99',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', $boundaryName)
            ->assertJsonPath('data.isbn', $boundaryIsbn)
            ->assertJsonPath('data.value', '9999999999.99');

        foreach ([
            ['name' => str_repeat('a', 256)],
            ['isbn' => str_repeat('9', 21)],
            ['value' => '10000000000.00'],
        ] as $payload) {
            $this->patchJson("/api/v1/books/{$book->id}", $payload)->assertStatus(422);
        }

        $book->refresh();
        $this->assertSame($boundaryName, $book->name);
        $this->assertSame($boundaryIsbn, $book->isbn);
        $this->assertSame('9999999999.99', $book->value);
    }

    public function test_update_validation_precedes_ownership_lookup(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create(['name' => 'Foreign Book']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$foreignBook->id}", ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->patchJson('/api/v1/books/999999', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $foreignBook->refresh();
        $this->assertSame('Foreign Book', $foreignBook->name);
    }

    public function test_update_rejects_foreign_and_missing_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create(['name' => 'Foreign Book']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/books/{$foreignBook->id}", ['name' => 'Hacked'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);

        $foreignBook->refresh();
        $this->assertSame('Foreign Book', $foreignBook->name);

        $this->patchJson('/api/v1/books/999999', ['name' => 'Missing'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);
    }

    public function test_unauthenticated_user_cannot_update_a_book(): void
    {
        $book = Book::factory()->create(['name' => 'Original Name']);

        $this->patchJson("/api/v1/books/{$book->id}", ['name' => 'Hacked'])
            ->assertStatus(401);

        $book->refresh();
        $this->assertSame('Original Name', $book->name);
    }

    public function test_update_response_does_not_expose_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/books/{$book->id}", [
            'name' => 'Updated Name',
        ])->assertOk();

        $response
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissing(['password', 'token']);
    }

    public function test_authenticated_user_can_delete_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertExactJson(['message' => 'Book deleted.']);

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_deleted_book_is_absent_from_list_and_detail(): void
    {
        $user = User::factory()->create();
        $deletedBook = Book::factory()->for($user)->create();
        $keptBook = Book::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/books/{$deletedBook->id}")->assertOk();

        $list = $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->assertSame([$keptBook->id], collect($list->json('data'))->pluck('id')->all());

        $this->getJson("/api/v1/books/{$deletedBook->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);
    }

    public function test_delete_rejects_foreign_and_missing_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create(['name' => 'Foreign Book']);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/books/{$foreignBook->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);

        $this->assertDatabaseHas('books', [
            'id' => $foreignBook->id,
            'user_id' => $otherUser->id,
            'name' => 'Foreign Book',
        ]);

        $this->deleteJson('/api/v1/books/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Book not found.']);
    }

    public function test_unauthenticated_user_cannot_delete_a_book(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/api/v1/books/{$book->id}")->assertStatus(401);

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
