<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MaterialCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => MaterialResource::collection($this->collection),
            'meta' => [
                'current_page' => $this->currentPage(), // trang hiện tại
                'last_page' => $this->lastPage(), // trang cuối cùng
                'per_page' => $this->perPage(), // số bản ghi trên 1 trang
                'total' => $this->total(), // tổng số bản ghi
            ],
        ];
    }
}
