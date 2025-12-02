<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'action' => $this->action,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'description' => $this->description,
            'created_at' => $this->created_at,
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
        ];
    }
}