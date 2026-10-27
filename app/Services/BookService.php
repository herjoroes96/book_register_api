<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BookService
{
    public function paginateForUser(User $user, int $page = 1): LengthAwarePaginator
    {
        return $user->books()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'page', max(1, $page));
    }

    /**
     * @param  array{name: string, isbn: string, value: int|float|string}  $data
     */
    public function createForUser(User $user, array $data): Book
    {
        return $user->books()->create($data);
    }

    public function findForUser(User $user, int $bookId): Book
    {
        return $user->books()->findOrFail($bookId);
    }

    /**
     * @param  array{name?: string, isbn?: string, value?: int|float|string}  $data
     */
    public function updateForUser(User $user, int $bookId, array $data): Book
    {
        $book = $this->findForUser($user, $bookId);
        $book->update($data);

        return $book->refresh();
    }

    public function deleteForUser(User $user, int $bookId): void
    {
        $this->findForUser($user, $bookId)->delete();
    }
}
