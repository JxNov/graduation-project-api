<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AcademicYearCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => AcademicYearResource::collection($this->collection),
        ];
    }
}
