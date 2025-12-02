<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Get paginated tickets with filters
     */
    public function getPaginatedTickets(Request $request, ?int $userId = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Ticket::with(['category', 'user', 'assignedUser'])
            ->withCount('comments');

        // If user is not admin, only show their tickets or assigned tickets
        if ($userId && !$request->user()->isAdmin()) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('assigned_to', $userId);
            });
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new ticket
     */
    public function createTicket(StoreTicketRequest $request): Ticket
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $data['attachments'] = $this->handleAttachments($request->file('attachments'));
        }

        $ticket = Ticket::create($data);

        // Log activity
        ActivityLog::log(
            $ticket->id,
            $request->user()->id,
            'created',
            null,
            $ticket->toArray(),
            'Ticket created'
        );

        return $ticket->load(['category', 'user']);
    }

    /**
     * Update a ticket
     */
    public function updateTicket(UpdateTicketRequest $request, Ticket $ticket): Ticket
    {
        $oldValues = $ticket->toArray();
        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $newAttachments = $this->handleAttachments($request->file('attachments'));
            $data['attachments'] = array_merge($ticket->attachments ?? [], $newAttachments);
        }

        $ticket->update($data);

        // Log activity for changed fields
        $changes = $ticket->getChanges();
        if (!empty($changes)) {
            $description = $this->generateChangeDescription($changes);
            
            ActivityLog::log(
                $ticket->id,
                $request->user()->id,
                'updated',
                $oldValues,
                $ticket->toArray(),
                $description
            );
        }

        return $ticket->load(['category', 'user', 'assignedUser']);
    }

    /**
     * Delete a ticket
     */
    public function deleteTicket(Request $request, Ticket $ticket): void
    {
        // Delete attachments from storage
        if ($ticket->attachments) {
            foreach ($ticket->attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }
        }

        // Log activity
        ActivityLog::log(
            $ticket->id,
            $request->user()->id,
            'deleted',
            $ticket->toArray(),
            null,
            'Ticket deleted'
        );

        $ticket->delete();
    }

    /**
     * Get ticket statistics
     */
    public function getTicketStats(?int $userId = null): array
    {
        $query = Ticket::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('assigned_to', $userId);
            });
        }

        return [
            'total' => $query->count(),
            'open' => $query->clone()->byStatus('open')->count(),
            'in_progress' => $query->clone()->byStatus('in_progress')->count(),
            'resolved' => $query->clone()->byStatus('resolved')->count(),
            'closed' => $query->clone()->byStatus('closed')->count(),
            'high_priority' => $query->clone()->byPriority('high')->count(),
        ];
    }

    /**
     * Handle file attachments
     */
    private function handleAttachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store('attachments', 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        return $attachments;
    }

    /**
     * Generate description for changes
     */
    private function generateChangeDescription(array $changes): string
    {
        $descriptions = [];

        foreach ($changes as $field => $newValue) {
            switch ($field) {
                case 'status':
                    $descriptions[] = "Status changed to {$newValue}";
                    break;
                case 'priority':
                    $descriptions[] = "Priority changed to {$newValue}";
                    break;
                case 'assigned_to':
                    $descriptions[] = $newValue ? "Assigned to user ID {$newValue}" : "Unassigned";
                    break;
                case 'category_id':
                    $descriptions[] = "Category changed";
                    break;
                default:
                    $descriptions[] = ucfirst($field) . " updated";
                    break;
            }
        }

        return implode(', ', $descriptions);
    }
}