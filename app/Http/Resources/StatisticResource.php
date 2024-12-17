<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatisticResource extends JsonResource
{
    // StatisticResource.php
    public function toArray(Request $request): array
    {
        return [
            'className' => $this->class_name,
            'subjectName' => $this->subject_name,
            'semesterName' => $this->semester_slug,
            'total' => [
                'x_less_than_3_5' => $this->total_less_than_3_5,
                'between_3_5_5' => $this->total_between_3_5_5,
                'between_5_6_5' => $this->total_between_5_6_5,
                'between_6_5_8' => $this->total_between_6_5_8,
                'between_8_9' => $this->total_between_8_9,
                'above_9' => $this->total_above_9,
            ],
            'averageScore' => $this->average_score,
        ];
    }
}
