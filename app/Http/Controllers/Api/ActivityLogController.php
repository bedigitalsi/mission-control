<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::orderByDesc('created_at');

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $paginated = $query->paginate($request->get('limit', 50));

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $log = ActivityLog::create($request->validate([
            'type' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'agent' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]));

        return response()->json(['success' => true, 'data' => $log], 201);
    }
}
