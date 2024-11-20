<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubjectTeacherCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
{
    return [
        'data' => SubjectTeacherResource::collection($this->collection),  // Sử dụng collection của resource
        'meta' => [
            'current_page' => $this->currentPage(),  // Trang hiện tại
            'last_page' => $this->lastPage(),        // Trang cuối cùng
            'per_page' => $this->perPage(),          // Số bản ghi trên một trang
            'total' => $this->total(),               // Tổng số bản ghi
        ],
    ];
}

}
