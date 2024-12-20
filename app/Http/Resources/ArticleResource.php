<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        return [
            'id' => $this->id,
            'content' => $this->content,
            'teacherName' => ($this->teacher)->name,
            'teacherImage' => ($this->teacher)->image,
            'className' => ($this->class)->name,
            'createdAt' => $this->created_at
        ];
    }
}
