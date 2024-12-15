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
            'subjectName' => $this->subject->name,
            'subjectSlug' => $this->subject->slug,
            'teacherName' =>  $this->teacher->name,
            'teacherImage' => $this->teacher->image,
//            'class' => $this->class->slug,
//            'semester' => $this->whenLoaded('semester', function () {
//                return $this->semester ? [
//                    'name' => $this->semester->name,
//                    'slug' => $this->semester->slug,
//                ] : null;
//            }),
            'semesterSlug' => $this->semester->slug,
            'semesterName' => $this->semester->name,
        ];
    }
}
