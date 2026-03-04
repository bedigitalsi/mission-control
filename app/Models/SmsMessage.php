<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $fillable = [
        'direction', 'phone_number', 'message', 'sender_name', 'status',
        'provider', 'from_name', 'device_id', 'external_id', 'meta', 'sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
    ];
}
