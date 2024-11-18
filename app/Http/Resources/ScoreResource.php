<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
//            'studentId' => $this->student_id,
//            'subjectId' => $this->subject_id,
//            'semesterId' => $this->semester_id,
            'studentName' => optional($this->student)->name,
            'subjectName' => optional($this->subject)->name,
            'className' => optional($this->class)->name,
            'semesterName' => $this->semester ? [
                'name' => $this->semester->name,
                'academicYearName' => optional($this->semester->academicYear)->name
            ] : null,
            'detailedScores' => $this->detailed_scores,
            'averageScore' => $this->average_score,
        ];
    }
}
