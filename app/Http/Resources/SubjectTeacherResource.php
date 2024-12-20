<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectTeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'teacherName' => $this->teacher_name, 
            'subjectName' => $this->subject_name, 
            'username' => $this->username,        
            'subjectSlug' => $this->subject_slug,
        ];
    }

}
