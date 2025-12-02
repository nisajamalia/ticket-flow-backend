<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Tickets API endpoints
Route::get('/tickets', function (Request $request) {
    $page = max(1, (int)$request->query('page', 1));
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    $search = $request->query('search', '');
    $statusFilter = $request->query('status', '');
    $priorityFilter = $request->query('priority', '');
    $categoryFilter = $request->query('category', '');
    $sort = $request->query('sort', 'newest');
    $showArchived = $request->query('archived', '0') === '1';

    $query = DB::table('tickets as t')
        ->leftJoin('categories as c', 't.category_id', '=', 'c.id')
        ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
        ->select(
            't.id', 't.title', 't.description', 't.status', 't.priority',
            't.created_at', 't.updated_at', 't.archived',
            'c.name as category_name', 'u.name as assigned_to_name'
        )
        ->where('t.archived', $showArchived ? 1 : 0);

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('t.title', 'LIKE', "%{$search}%")
              ->orWhere('t.description', 'LIKE', "%{$search}%");
        });
    }

    if (!empty($statusFilter)) $query->where('t.status', $statusFilter);
    if (!empty($priorityFilter)) $query->where('t.priority', $priorityFilter);
    if (!empty($categoryFilter)) $query->where('t.category_id', $categoryFilter);

    switch ($sort) {
        case 'oldest': $query->orderBy('t.created_at', 'ASC'); break;
        case 'priority': $query->orderByRaw("FIELD(t.priority, 'urgent', 'high', 'medium', 'low')"); break;
        case 'status': $query->orderByRaw("FIELD(t.status, 'open', 'in-progress', 'resolved', 'closed')"); break;
        default: $query->orderBy('t.created_at', 'DESC'); break;
    }

    $total = $query->count();
    $tickets = $query->offset($offset)->limit($perPage)->get();

    return response()->json([
        'success' => true,
        'tickets' => $tickets,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ]
    ]);
});

Route::get('/tickets/{id}', function ($id) {
    $ticket = DB::table('tickets as t')
        ->leftJoin('categories as c', 't.category_id', '=', 'c.id')
        ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
        ->select('t.*', 'c.name as category_name', 'u.name as assigned_to_name')
        ->where('t.id', $id)
        ->first();

    if (!$ticket) {
        return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    return response()->json(['success' => true, 'ticket' => $ticket]);
});

Route::post('/tickets', function (Request $request) {
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'status' => 'required|in:open,in-progress,resolved,closed',
        'priority' => 'required|in:low,medium,high,urgent',
        'category_id' => 'required|integer',
        'assigned_to' => 'nullable|integer'
    ]);

    $id = DB::table('tickets')->insertGetId([
        'title' => $request->title,
        'description' => $request->description,
        'status' => $request->status,
        'priority' => $request->priority,
        'category_id' => $request->category_id,
        'assigned_to' => $request->assigned_to,
        'archived' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json(['success' => true, 'message' => 'Ticket created successfully', 'ticket_id' => $id], 201);
});

Route::put('/tickets/{id}', function (Request $request, $id) {
    $action = $request->query('action');

    if ($action === 'archive' || $action === 'unarchive') {
        $archived = ($action === 'archive') ? 1 : 0;
        $affected = DB::table('tickets')->where('id', $id)->update(['archived' => $archived, 'updated_at' => now()]);
        
        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        $message = $action === 'archive' ? 'Ticket archived successfully' : 'Ticket unarchived successfully';
        return response()->json(['success' => true, 'message' => $message]);
    }

    $request->validate([
        'title' => 'sometimes|required|string|max:255',
        'description' => 'sometimes|required|string',
        'status' => 'sometimes|required|in:open,in-progress,resolved,closed',
        'priority' => 'sometimes|required|in:low,medium,high,urgent',
        'category_id' => 'sometimes|required|integer',
        'assigned_to' => 'nullable|integer'
    ]);

    $affected = DB::table('tickets')
        ->where('id', $id)
        ->update(array_merge(
            $request->only(['title', 'description', 'status', 'priority', 'category_id', 'assigned_to']),
            ['updated_at' => now()]
        ));

    if ($affected === 0) {
        return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    return response()->json(['success' => true, 'message' => 'Ticket updated successfully']);
});

Route::delete('/tickets/{id}', function ($id) {
    $affected = DB::table('tickets')->where('id', $id)->delete();
    
    if ($affected === 0) {
        return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    return response()->json(['success' => true, 'message' => 'Ticket deleted successfully']);
});

// Dashboard stats endpoint
Route::get('/stats', function () {
    $stats = [
        'total' => DB::table('tickets')->where('archived', 0)->count(),
        'resolved' => DB::table('tickets')->where('archived', 0)->where('status', 'resolved')->count(),
        'in_progress' => DB::table('tickets')->where('archived', 0)->where('status', 'in-progress')->count(),
        'high_priority' => DB::table('tickets')->where('archived', 0)->whereIn('priority', ['high', 'urgent'])->count(),
        'open' => DB::table('tickets')->where('archived', 0)->where('status', 'open')->count(),
        'closed' => DB::table('tickets')->where('archived', 0)->where('status', 'closed')->count()
    ];

    return response()->json($stats);
});

// Categories endpoint
Route::get('/categories', function () {
    $categories = DB::table('categories')->select('id', 'name')->get();
    return response()->json($categories);
});