<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PublicUserResource;

class PromptEditResource extends JsonResource
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
            'category_id' => $this->category_id,
            'content' => $this->content,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'coverimage_id' => $this->coverimage_id,
        ];
    }
}