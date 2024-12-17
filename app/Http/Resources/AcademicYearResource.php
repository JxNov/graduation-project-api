<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearResource extends JsonResource
{
  // optional(): 1 hàm trợ giúp, dùng để xử lý tình huống 1 đối tượng có thể là null mà không gây lỗi
  // vì nếu xóa từ generation (khóa học) thì khóa học kh còn nữa ==> generation->name là null
  public function toArray(Request $request): array
  {
    return [
      'name' => $this->name,
      'slug' => $this->slug,
      'startDate' => Carbon::parse($this->start_date)->format('d/m/Y'),
      'endDate' => Carbon::parse($this->end_date)->format('d/m/Y'),
      'generationId' => $this->generation_id,
      'generationName' => optional($this->generation)->name
    ];
  }
}
