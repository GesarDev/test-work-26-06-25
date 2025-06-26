<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function listUsersByBook(Book $book): JsonResponse
    {
        $bookId = $book->id;
        $users = \DB::table('users')
            ->select([
                'id' => 'users.id',
                'name' => 'users.name',
                \DB::raw("MAX(CASE WHEN user_properties.key = 'email' THEN user_properties.value END) as email"),
                \DB::raw("MAX(CASE WHEN user_properties.key = 'phone' THEN user_properties.value END) as phone"),
            ])
            ->leftJoin('bookmarks', 'users.id', '=', 'bookmarks.user_id')
            ->leftJoin('user_properties', 'users.id', '=', 'user_properties.user_id')
            ->where('bookmarks.book_id', '=', $bookId)
            ->whereIn('user_properties.key',
                [
                    'email',
                    'phone',
                ]
            )
            ->groupBy('users.id', 'users.name')
        ->get();

        return response()->json($users);
    }
}
