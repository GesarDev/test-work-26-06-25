<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'description',
        'pages_count',
        'rowid',
    ];

    public function bookmarks(): HasMany
    {
        return  $this->hasMany(Bookmark::class, 'book_id');
    }
}
