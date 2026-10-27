<?php

namespace Webkul\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Telegram\Contracts\Message as MessageContract;

class Message extends Model implements MessageContract
{
    protected $table = 'messaging_messages';

    protected $fillable = [
        'channel',
        'external_id',
        'chat_id',
        'from_number',
        'contact_name',
        'content',
        'direction',
        'status',
        'read_at',
        'sent_at',
        'person_id',
        'lead_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];
}
