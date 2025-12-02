<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\TicketResource;
use App\Models\User;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    /**
     * Get admin dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = $this->ticketService->getTicketStats();
        
        // Get recent tickets
        $recentTickets = Ticket::with(['category', 'user', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get user statistics
        $userStats = [
            'total_users' => User::count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'regular_users' => User::where('role', 'user')->count(),
        ];

        return response()->json([
            'data' => [
                'ticket_stats' => $stats,
                'user_stats' => $userStats,
                'recent_tickets' => TicketResource::collection($recentTickets),
            ],
        ]);
    }

    /**
     * Get all users (Admin only)
     */
    public function users(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::orderBy('name')->paginate(20);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Update user role (Admin only)
     */
    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user->update(['role' => $request->role]);

        return response()->json([
            'message' => 'User role updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Get system statistics
     */
    public function systemStats(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = [
            'tickets' => [
                'total' => Ticket::count(),
                'today' => Ticket::whereDate('created_at', today())->count(),
                'this_week' => Ticket::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Ticket::whereMonth('created_at', now()->month)->count(),
            ],
            'users' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'active_today' => User::whereDate('updated_at', today())->count(),
            ],
            'tickets_by_status' => [
                'open' => Ticket::where('status', 'open')->count(),
                'in_progress' => Ticket::where('status', 'in_progress')->count(),
                'resolved' => Ticket::where('status', 'resolved')->count(),
                'closed' => Ticket::where('status', 'closed')->count(),
            ],
            'tickets_by_priority' => [
                'low' => Ticket::where('priority', 'low')->count(),
                'medium' => Ticket::where('priority', 'medium')->count(),
                'high' => Ticket::where('priority', 'high')->count(),
            ],
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}