<?php

namespace Webkul\Whatsapp\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Whatsapp\Contracts\Message;

class MessageRepository extends Repository
{
    public function model(): string
    {
        return Message::class;
    }
}
