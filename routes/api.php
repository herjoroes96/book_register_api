<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');

        Route::get('/books', [BookController::class, 'index'])->name('api.v1.books.index');
        Route::post('/books', [BookController::class, 'store'])->name('api.v1.books.store');
        Route::get('/books/{book}', [BookController::class, 'show'])
            ->whereNumber('book')
            ->name('api.v1.books.show');
        Route::patch('/books/{book}', [BookController::class, 'update'])
            ->whereNumber('book')
            ->name('api.v1.books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])
            ->whereNumber('book')
            ->name('api.v1.books.destroy');
    });
});
