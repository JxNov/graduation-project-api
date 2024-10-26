<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'academicYearName' => optional($this->academicYear)->name,
            'className' => optional($this->class)->name
        ];
    }
}
