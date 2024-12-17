<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BlockCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => BlockResource::collection($this->collection),
        ];
    }
}
