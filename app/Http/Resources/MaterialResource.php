<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'filePath' => $this->file_path,
            'subjectName' => optional($this->subject)->name,
            'teacherName' => optional($this->teacher)->name,
        ];
    }
}
