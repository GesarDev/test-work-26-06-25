<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookmarkResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookmarkController extends Controller
{
    public function listForUser(User $user): AnonymousResourceCollection
    {
        $bookmarks = $user->bookmarks()->get();

        return BookmarkResource::collection($bookmarks);
    }
}
