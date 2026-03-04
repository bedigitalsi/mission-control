<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function index(Request $request)
    {
        $query = SmsMessage::orderBy('created_at', 'desc');

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('phone')) {
            $query->where('phone_number', 'like', '%' . $request->phone . '%');
        }
        if ($request->filled('sender')) {
            $query->where('sender_name', $request->sender);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('message', 'like', "%$s%")
                  ->orWhere('phone_number', 'like', "%$s%")
                  ->orWhere('sender_name', 'like', "%$s%");
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $perPage = min((int)($request->per_page ?? 50), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|in:incoming,outgoing',
            'phone_number' => 'required|string',
            'message' => 'required|string',
            'sender_name' => 'nullable|string',
            'status' => 'nullable|string',
            'provider' => 'nullable|string',
            'from_name' => 'nullable|string',
            'device_id' => 'nullable|string',
            'external_id' => 'nullable|string',
            'meta' => 'nullable|array',
            'sent_at' => 'nullable|date',
        ]);

        $sms = SmsMessage::create($validated);
        return response()->json(['success' => true, 'data' => $sms], 201);
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => SmsMessage::count(),
                'incoming' => SmsMessage::where('direction', 'incoming')->count(),
                'outgoing' => SmsMessage::where('direction', 'outgoing')->count(),
                'today' => SmsMessage::whereDate('created_at', today())->count(),
                'senders' => SmsMessage::distinct('sender_name')->pluck('sender_name')->filter()->values(),
            ],
        ]);
    }
}
