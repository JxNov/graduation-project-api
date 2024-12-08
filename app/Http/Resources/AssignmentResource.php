<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'dueDate' => $this->due_date,
            'criteria' => $this->criteria,
            'subject' => $this->subject->name,
            'teacher' =>  $this->teacher->name,
            'class' => $this->class->name,
            'semester' => $this->whenLoaded('semester', function () {
                return $this->semester ? [
                    'name' => $this->semester->name,
                    'academicYearName' => $this->semester->academicYear->name
                ] : null;
            }),
        ];
    }
}
