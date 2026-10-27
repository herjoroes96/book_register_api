<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreBookRequest;
use App\Http\Requests\Books\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Services\BookService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BookController extends Controller
{
    public function __construct(private readonly BookService $bookService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $page = max(1, (int) $request->query('page', 1));

        return BookResource::collection(
            $this->bookService->paginateForUser($request->user(), $page),
        );
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->createForUser($request->user(), $request->validated());

        return response()->json([
            'data' => new BookResource($book),
        ], 201);
    }

    public function show(Request $request, string $book): JsonResponse
    {
        try {
            $bookModel = $this->bookService->findForUser($request->user(), (int) $book);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return response()->json([
            'data' => new BookResource($bookModel),
        ]);
    }

    public function update(UpdateBookRequest $request, string $book): JsonResponse
    {
        try {
            $bookModel = $this->bookService->updateForUser(
                $request->user(),
                (int) $book,
                $request->validated(),
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return response()->json([
            'data' => new BookResource($bookModel),
        ]);
    }

    public function destroy(Request $request, string $book): JsonResponse
    {
        try {
            $this->bookService->deleteForUser($request->user(), (int) $book);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return response()->json(['message' => 'Book deleted.']);
    }
}
