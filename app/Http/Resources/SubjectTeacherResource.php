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
    public function toArray(Request $request): array
{
    
    $subjects = $this->subjects ? $this->subjects->map(function ($subject) {
        return $subject->name;
    })->toArray() : [];

    return [
        'teacher' => [
            'name' => $this->name, 
            'username' => $this->username, 
            'email' => $this->email, 
        ],
        'subjects' => $subjects,
    ];
}

}
