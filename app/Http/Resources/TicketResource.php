<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'attachments' => $this->attachments,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => new UserResource($this->whenLoaded('user')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'activity_logs' => ActivityLogResource::collection($this->whenLoaded('activityLogs')),
            
            // Counts
            'comments_count' => $this->whenCounted('comments'),
            
            // Computed attributes
            'priority_color' => $this->priority_color,
            'status_color' => $this->status_color,
        ];
    }
}