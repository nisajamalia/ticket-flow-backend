<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Http\Requests\StoreCommentRequest;
use Illuminate\Http\Request;

class CommentService
{
    /**
     * Get comments for a ticket
     */
    public function getTicketComments(Ticket $ticket, Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = $ticket->comments()->with('user');

        // If user is not admin, hide internal comments
        if (!$request->user()->isAdmin()) {
            $query->public();
        }

        return $query->get();
    }

    /**
     * Create a new comment
     */
    public function createComment(StoreCommentRequest $request, Ticket $ticket): Comment
    {
        $data = $request->validated();
        $data['ticket_id'] = $ticket->id;
        $data['user_id'] = $request->user()->id;

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $data['attachments'] = $this->handleAttachments($request->file('attachments'));
        }

        $comment = Comment::create($data);

        // Log activity
        ActivityLog::log(
            $ticket->id,
            $request->user()->id,
            'commented',
            null,
            ['comment_id' => $comment->id],
            'Comment added'
        );

        return $comment->load('user');
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, Comment $comment): Comment
    {
        $data = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $comment->update($data);

        // Log activity
        ActivityLog::log(
            $comment->ticket_id,
            $request->user()->id,
            'comment_updated',
            null,
            ['comment_id' => $comment->id],
            'Comment updated'
        );

        return $comment->load('user');
    }

    /**
     * Delete a comment
     */
    public function deleteComment(Request $request, Comment $comment): void
    {
        // Log activity
        ActivityLog::log(
            $comment->ticket_id,
            $request->user()->id,
            'comment_deleted',
            ['comment_id' => $comment->id],
            null,
            'Comment deleted'
        );

        $comment->delete();
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
}