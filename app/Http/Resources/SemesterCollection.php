<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SemesterCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => SemesterResource::collection($this->collection),
        ];
    }
}
