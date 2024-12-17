<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
{
    // Lấy tất cả học sinh trong lớp
    $students = $this->students->map(function ($student) {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'username' => $student->username,
            'email' => $student->email,
            'date_of_birth' => $student->date_of_birth,
            'gender' => $student->gender,
            'address' => $student->address,
            'phone_number' => $student->phone_number,
            
        ];
    });

    return [
        'id' => $this->id,
        'class' => $this->name,
        'students' => $students,
    ];
}
}