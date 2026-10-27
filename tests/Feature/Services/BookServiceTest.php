<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Book;
use App\Models\User;
use App\Services\BookService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_assigns_the_owner_and_preserves_leading_zero_isbn(): void
    {
        $user = User::factory()->create();

        $book = app(BookService::class)->createForUser($user, [
            'name' => 'The Pragmatic Programmer',
            'isbn' => '0012345678901',
            'value' => '12.50',
        ]);

        $this->assertSame($user->id, $book->user_id);
        $this->assertSame('0012345678901', $book->isbn);
        $this->assertSame('12.50', $book->value);
    }

    public function test_paginate_returns_only_the_owner_books_with_fixed_pagination_and_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $oldestBook = Book::factory()->for($user)->create();
        $oldestBook->forceFill(['created_at' => now()->subDay()])->save();

        Book::factory()->count(15)->for($user)->create();
        Book::factory()->for($otherUser)->create();

        $service = app(BookService::class);
        $firstPage = $service->paginateForUser($user);
        $secondPage = $service->paginateForUser($user, 2);

        $this->assertSame(16, $firstPage->total());
        $this->assertSame(15, $firstPage->perPage());
        $this->assertCount(15, $firstPage->items());
        $this->assertCount(1, $secondPage->items());

        foreach ($firstPage->items() as $book) {
            $this->assertSame($user->id, $book->user_id);
        }

        $this->assertNotSame($oldestBook->id, $firstPage->items()[0]->id);
        $this->assertSame($oldestBook->id, $secondPage->items()[0]->id);
    }

    public function test_pagination_breaks_created_at_ties_by_id_descending(): void
    {
        $user = User::factory()->create();
        $createdAt = now()->subMinute();

        $olderBook = Book::factory()->for($user)->create();
        $olderBook->forceFill(['created_at' => $createdAt])->save();

        $newerBook = Book::factory()->for($user)->create();
        $newerBook->forceFill(['created_at' => $createdAt])->save();

        $items = app(BookService::class)->paginateForUser($user)->items();

        $this->assertSame(
            [$newerBook->id, $olderBook->id],
            array_map(static fn (Book $book): int => $book->id, $items),
        );
    }

    public function test_duplicate_isbn_is_allowed(): void
    {
        $user = User::factory()->create();
        $service = app(BookService::class);

        $data = [
            'name' => 'Duplicate ISBN',
            'isbn' => '9780135957059',
            'value' => '49.90',
        ];

        $first = $service->createForUser($user, $data);
        $second = $service->createForUser($user, $data);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->isbn, $second->isbn);
        $this->assertDatabaseCount('books', 2);
    }

    public function test_find_for_user_returns_an_owned_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $found = app(BookService::class)->findForUser($user, $book->id);

        $this->assertSame($book->id, $found->id);
        $this->assertSame($user->id, $found->user_id);
    }

    public function test_find_for_user_rejects_a_foreign_book(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create();

        $this->expectException(ModelNotFoundException::class);

        app(BookService::class)->findForUser($user, $foreignBook->id);
    }

    public function test_find_for_user_rejects_a_missing_book(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(BookService::class)->findForUser($user, 999999);
    }

    public function test_update_changes_only_supplied_fields_and_keeps_the_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);

        $updated = app(BookService::class)->updateForUser($user, $book->id, [
            'name' => 'Updated Name',
            'user_id' => $otherUser->id,
        ]);

        $this->assertSame('Updated Name', $updated->name);
        $this->assertSame('9780135957059', $updated->isbn);
        $this->assertSame('10.00', $updated->value);
        $this->assertSame($user->id, $updated->user_id);
    }

    public function test_update_changes_multiple_fields_and_preserves_leading_zero_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'name' => 'Original Name',
            'isbn' => '9780135957059',
            'value' => '10.00',
        ]);

        $updated = app(BookService::class)->updateForUser($user, $book->id, [
            'isbn' => '0012345678901',
            'value' => '54.90',
        ]);

        $this->assertSame('Original Name', $updated->name);
        $this->assertSame('0012345678901', $updated->isbn);
        $this->assertSame('54.90', $updated->value);
    }

    public function test_update_accepts_a_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        Book::factory()->for($user)->create(['isbn' => '1111111111111']);

        $updated = app(BookService::class)->updateForUser($user, $book->id, [
            'isbn' => '1111111111111',
        ]);

        $this->assertSame('1111111111111', $updated->isbn);
    }

    public function test_update_rejects_a_foreign_book_and_leaves_it_unchanged(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create(['name' => 'Foreign Book']);

        try {
            app(BookService::class)->updateForUser($user, $foreignBook->id, [
                'name' => 'Hacked',
            ]);
            $this->fail('Expected a not-found exception.');
        } catch (ModelNotFoundException) {
            // Expected.
        }

        $foreignBook->refresh();
        $this->assertSame('Foreign Book', $foreignBook->name);
    }

    public function test_update_rejects_a_missing_book(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(BookService::class)->updateForUser($user, 999999, ['name' => 'Missing']);
    }

    public function test_delete_removes_an_owned_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        app(BookService::class)->deleteForUser($user, $book->id);

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseCount('books', 0);
    }

    public function test_delete_rejects_a_foreign_book_and_leaves_it_intact(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignBook = Book::factory()->for($otherUser)->create();

        try {
            app(BookService::class)->deleteForUser($user, $foreignBook->id);
            $this->fail('Expected a not-found exception.');
        } catch (ModelNotFoundException) {
            // Expected.
        }

        $this->assertDatabaseHas('books', ['id' => $foreignBook->id]);
    }

    public function test_delete_rejects_a_missing_book(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(BookService::class)->deleteForUser($user, 999999);
    }
}
