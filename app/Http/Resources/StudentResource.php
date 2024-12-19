<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'username'=>$this->username,
            'image'=>$this->image,
            'dateOfBirth' => Carbon::parse($this->date_of_birth)->format('d/m/Y'),
            'gender' => $this->gender,
            'address' => $this->address,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
        ];
    }
}
