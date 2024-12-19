<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'teacherName' => optional($this->teacher)->name,
            'username' => optional($this->teacher)->username,
            'academicYearName' => $this->academicYears->pluck('name')->implode(', '),
            'academicYearSlug' => $this->academicYears->pluck('slug')->implode(', '),
            'blockSlug' => $this->blocks->pluck('slug')->implode(', '),
            'numberOfStudents' => $this->students->count()
        ];
    }
}
