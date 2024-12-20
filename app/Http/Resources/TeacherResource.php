<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'image' => $this->image,
            'dateOfBirth' => Carbon::parse($this->date_of_birth)->format('d/m/Y'),
            'gender' => $this->gender,
            'address' => $this->address,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
            // 'password' => $this->password,
            'subjects' => $this->subjects->pluck('name'),
        ];
    }
}
