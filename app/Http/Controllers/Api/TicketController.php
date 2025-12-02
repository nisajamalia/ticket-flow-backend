<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Http\Resources\ActivityLogResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    /**
     * Display a listing of tickets
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->isAdmin() ? null : $request->user()->id;
        $tickets = $this->ticketService->getPaginatedTickets($request, $userId);

        return response()->json([
            'data' => TicketResource::collection($tickets->items()),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ],
            'links' => [
                'first' => $tickets->url(1),
                'last' => $tickets->url($tickets->lastPage()),
                'prev' => $tickets->previousPageUrl(),
                'next' => $tickets->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created ticket
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createTicket($request);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        // Check if user can view this ticket
        if (!$request->user()->isAdmin() && 
            $ticket->user_id !== $request->user()->id && 
            $ticket->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->load(['category', 'user', 'assignedUser', 'comments.user', 'activityLogs.user']);

        return response()->json([
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Update the specified ticket
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // Check if user can update this ticket
        if (!$request->user()->isAdmin() && $ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket = $this->ticketService->updateTicket($request, $ticket);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        // Check if user can delete this ticket
        if (!$request->user()->isAdmin() && $ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->ticketService->deleteTicket($request, $ticket);

        return response()->json([
            'message' => 'Ticket deleted successfully',
        ]);
    }

    /**
     * Get ticket statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->isAdmin() ? null : $request->user()->id;
        $stats = $this->ticketService->getTicketStats($userId);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get ticket activity logs
     */
    public function activityLogs(Request $request, Ticket $ticket): JsonResponse
    {
        // Check if user can view this ticket
        if (!$request->user()->isAdmin() && 
            $ticket->user_id !== $request->user()->id && 
            $ticket->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activityLogs = $ticket->activityLogs()->with('user')->get();

        return response()->json([
            'data' => ActivityLogResource::collection($activityLogs),
        ]);
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        // Only admins can assign tickets
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldAssignedTo = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $request->assigned_to]);

        // Log activity
        $description = $request->assigned_to 
            ? "Ticket assigned to user ID {$request->assigned_to}"
            : "Ticket unassigned";

        \App\Models\ActivityLog::log(
            $ticket->id,
            $request->user()->id,
            'assigned',
            ['assigned_to' => $oldAssignedTo],
            ['assigned_to' => $request->assigned_to],
            $description
        );

        return response()->json([
            'message' => 'Ticket assignment updated successfully',
            'data' => new TicketResource($ticket->load(['assignedUser'])),
        ]);
    }
}