<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookmarkController;
use Illuminate\Support\Facades\Route;

Route::get('/users/{user}/bookmarks', [BookmarkController::class, 'listForUser'])
    ->name('users.bookmarks.list');

Route::get('/books/{book}/users', [BookController::class, 'listUsersByBook'])
    ->name('books.users.list');
