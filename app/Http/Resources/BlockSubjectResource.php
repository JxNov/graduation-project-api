<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockSubjectResource extends JsonResource
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
            'nameBlock' => $this->name,
            'subjects' => $this->subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'nameSubject' => $subject->name,
                ];
            }),
        ];
    }
}
