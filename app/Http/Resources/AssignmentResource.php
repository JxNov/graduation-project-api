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
            'subject' => $this->whenLoaded('subject', function () {
                return $this->subject ? [
                    'name' => $this->subject->name,
                    'blockLevel' => $this->subject->block_level,
                ] : null;
            }),
            'teacher' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? [
                    'name' => $this->teacher->name,
                    'email' => $this->teacher->email,
                ] : null;
            }),
            'class' => $this->whenLoaded('class', function () {
                return $this->class ? [
                    'name' => $this->class->name,
                ] : null;
            }),
            'semester' => $this->whenLoaded('semester', function () {
                return $this->semester ? [
                    'name' => $this->semester->name,
                    'academicYearName' => $this->semester->academicYear->name
                ] : null;
            }),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
