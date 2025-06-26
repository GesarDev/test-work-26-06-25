<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $fillable = [
        'name',
        'rowid',
    ];

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
}
