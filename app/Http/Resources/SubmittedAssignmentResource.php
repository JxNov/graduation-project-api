<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmittedAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'studentUsername' => optional($this->student)->username,
            'studentName' => optional($this->student)->name,
            'filePath' => $this->file_path,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'submittedAt' => $this->submitted_at->toDateTimeString(),
        ];
    }
}
