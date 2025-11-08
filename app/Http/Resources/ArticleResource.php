<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both Eloquent models and raw DB results
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'article_url' => $this->article_url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at,
            'source' => [
                'id' => $this->source_id ?? null,
                'name' => $this->source_name ?? null,
                'slug' => $this->source_slug ?? null,
            ],
            'category' => [
                'id' => $this->category_id ?? null,
                'name' => $this->category_name ?? null,
                'slug' => $this->category_slug ?? null,
            ],
            'author' => [
                'id' => $this->author_id ?? null,
                'name' => $this->author_name ?? null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
