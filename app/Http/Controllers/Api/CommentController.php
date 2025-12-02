<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    /**
     * Display comments for a ticket
     */
    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        // Check if user can view this ticket
        if (!$request->user()->isAdmin() && 
            $ticket->user_id !== $request->user()->id && 
            $ticket->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comments = $this->commentService->getTicketComments($ticket, $request);

        return response()->json([
            'data' => CommentResource::collection($comments),
        ]);
    }

    /**
     * Store a newly created comment
     */
    public function store(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        // Check if user can comment on this ticket
        if (!$request->user()->isAdmin() && 
            $ticket->user_id !== $request->user()->id && 
            $ticket->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment = $this->commentService->createComment($request, $ticket);

        return response()->json([
            'message' => 'Comment created successfully',
            'data' => new CommentResource($comment),
        ], 201);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        // Check if user can update this comment
        if (!$request->user()->isAdmin() && $comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment = $this->commentService->updateComment($request, $comment);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        // Check if user can delete this comment
        if (!$request->user()->isAdmin() && $comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->commentService->deleteComment($request, $comment);

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}