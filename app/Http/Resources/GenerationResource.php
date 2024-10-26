<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'year' => $this->year,
            'startDate' => Carbon::parse($this->start_date)->format('d/m/Y'),
            'endDate' => Carbon::parse($this->end_date)->format('d/m/Y')
        ];
    }
}
