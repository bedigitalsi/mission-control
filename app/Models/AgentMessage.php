<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMessage extends Model
{
    protected $fillable = [
        "from_agent",
        "to_agent",
        "message",
        "response",
    ];
}
