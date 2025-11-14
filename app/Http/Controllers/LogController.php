<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of logs.
     *
     * GET /api/logs
     */
    public function index(Request $request)
    {
        $query = Log::with('user');

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filter by resource type
        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Only master can see all logs
        if ($request->user()->isNormal()) {
            $query->where('user_id', $request->user()->id);
        }

        $logs = $query->latest()->paginate(50);

        return response()->json($logs);
    }

    /**
     * Display the specified log.
     *
     * GET /api/logs/{id}
     */
    public function show(Request $request, Log $log)
    {
        // Only master or the log owner can view
        if ($request->user()->isNormal() && $log->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json($log->load('user'));
    }
}
