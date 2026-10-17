<?php

namespace Webkul\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Whatsapp\Contracts\Message as MessageContract;

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
        'sent_at',
        'person_id',
        'lead_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
