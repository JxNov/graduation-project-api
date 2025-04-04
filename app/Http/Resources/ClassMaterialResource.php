<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'filePath' => $this->file_path,
            'subjectName' => optional($this->subject)->name,
            'subjectSlug' => optional($this->subject)->slug,
        ];
    }
}