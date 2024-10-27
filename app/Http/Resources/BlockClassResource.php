<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'blockName' => optional($this->block)->name,
            'className' => optional($this->class)->name,
        ];
    }
}
