<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'book' => new BookResource($this->book),
            'bookmark' => $this->bookmark
        ];
    }
}
