<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubjectClassCollection extends ResourceCollection
{
    public $additional = [
        'meta' => [
            'status' => 'success',
        ],
    ];
    public function toArray($request)
    {
        return [
            'data' => SubjectClassResource::collection($this->collection),
            'meta' => [
                'current_page' => $this->currentPage(), // trang hiện tại
                'last_page' => $this->lastPage(), // trang cuối cùng
                'per_page' => $this->perPage(), // số bản ghi trên 1 trang
                'total' => $this->total(), // tổng số bản ghi
            ],
        ];
    }
}
