<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PublicUserResource;

class PromptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'coverimage_id' => $this->coverimage_id,
            'user'     => PublicUserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}