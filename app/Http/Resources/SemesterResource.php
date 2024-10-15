<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SemesterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'start_date' => Carbon::parse($this->start_date)->format('d/m/Y'),
            'end_date' => Carbon::parse($this->end_date)->format('d/m/Y'),
            'academic_year_id' => $this->academic_year_id,
            'generation_name' => optional($this->academicYear->generation)->name,
            'academic_year_name' => optional($this->academicYear)->name
        ];
    }
}
