<?php

namespace Webkul\Whatsapp\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Whatsapp\Repositories\MessageRepository;
use Webkul\Whatsapp\Services\WhatsappClient;

class MessageController extends Controller
{
    public function __construct(
        protected MessageRepository $messages,
        protected WhatsappClient $client,
    ) {}

    public function index()
    {
        return view('whatsapp::admin.index', [
            'chats' => $this->chatList(),
        ]);
    }

    public function thread(Request $request): JsonResponse
    {
        $chatId = (string) $request->query('chat_id', '');

        if ($chatId === '') {
            return response()->json(['messages' => [], 'chats' => $this->chatList()]);
        }

        $messages = $this->messages->getModel()
            ->where('chat_id', $chatId)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'content' => $m->content,
                'status' => $m->status,
                'contact_name' => $m->contact_name,
                'from_number' => $m->from_number,
                'sent_at' => optional($m->sent_at)->toIso8601String()
                    ?? optional($m->created_at)->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'messages' => $messages,
            'chats' => $this->chatList(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|string',
            'number' => 'nullable|string',
            'text' => 'required|string',
        ]);

        $number = $data['number'] ?: $data['chat_id'];

        $result = $this->client->send($number, $data['text']);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? 'send_failed',
            ], 502);
        }

        $message = $this->messages->create([
            'channel' => 'whatsapp',
            'external_id' => $result['external_id'] ?? null,
            'chat_id' => $result['chat_id'] ?? $data['chat_id'],
            'from_number' => $number,
            'contact_name' => null,
            'content' => $data['text'],
            'direction' => 'outgoing',
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'person_id' => $this->resolvePersonId($data['chat_id']),
            'lead_id' => $this->resolveLeadId($data['chat_id']),
        ]);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => $message->id,
                'direction' => 'outgoing',
                'content' => $message->content,
                'status' => $message->status,
                'sent_at' => optional($message->sent_at)->toIso8601String(),
            ],
        ]);
    }

    protected function chatList(): array
    {
        $prefix = DB::getTablePrefix();
        $table = $prefix.'messaging_messages';

        $latestIds = DB::table('messaging_messages')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('chat_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return [];
        }

        return DB::table('messaging_messages')
            ->whereIn('id', $latestIds)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'chat_id' => $r->chat_id,
                'contact_name' => $r->contact_name ?: $r->from_number,
                'from_number' => $r->from_number,
                'last_content' => $r->content,
                'last_direction' => $r->direction,
                'last_at' => $r->sent_at ?: $r->created_at,
            ])
            ->toArray();
    }

    protected function resolvePersonId(string $chatId): ?int
    {
        $id = DB::table('messaging_messages')
            ->where('chat_id', $chatId)
            ->whereNotNull('person_id')
            ->value('person_id');

        return $id ? (int) $id : null;
    }

    protected function resolveLeadId(string $chatId): ?int
    {
        $id = DB::table('messaging_messages')
            ->where('chat_id', $chatId)
            ->whereNotNull('lead_id')
            ->value('lead_id');

        return $id ? (int) $id : null;
    }
}
