<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'class' => $this->class,
            'schedule' => $this->schedule,
        ];
    }
}
