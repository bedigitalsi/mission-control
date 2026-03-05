<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentMessage;
use Illuminate\Http\Request;

class AgentMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentMessage::orderBy("created_at", "desc");

        if ($request->has("agent")) {
            $agent = $request->input("agent");
            $query->where(function($q) use ($agent) {
                $q->where("from_agent", $agent)->orWhere("to_agent", $agent);
            });
        }

        if ($request->has("unread")) {
            $query->whereNull("read_at");
        }

        if ($request->has("to")) {
            $query->where("to_agent", $request->input("to"));
        }

        $messages = $query->limit($request->input("limit", 50))->get();

        return response()->json(["success" => true, "data" => $messages]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "from_agent" => "required|string",
            "to_agent" => "required|string",
            "message" => "required|string",
            "priority" => "nullable|string",
            "context" => "nullable|string",
        ]);

        $msg = AgentMessage::create($validated);

        return response()->json(["success" => true, "data" => $msg], 201);
    }

    public function markRead($id)
    {
        $msg = AgentMessage::findOrFail($id);
        $msg->update(["read_at" => now()]);
        return response()->json(["success" => true]);
    }

    public function reply($id, Request $request)
    {
        $msg = AgentMessage::findOrFail($id);
        $msg->update([
            "response" => $request->input("response"),
            "read_at" => now(),
        ]);
        return response()->json(["success" => true, "data" => $msg]);
    }
}
