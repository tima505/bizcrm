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
            return response()->json([
                'messages' => [],
                'chats' => $this->chatList(),
                'lead_id' => null,
            ]);
        }

        $this->markChatAsRead($chatId);

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
            'lead_id' => $this->resolveLeadId($chatId),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|string',
            'number' => 'nullable|string',
            'text' => 'required|string',
        ]);

        /**
         * Always route by the chat_id (the full WhatsApp JID we got on the
         * inbound side). "number" is only kept as a display hint and may be
         * a non-routable string like "lid:12345" or "+7...". The JID is what
         * actually tells WhatsApp which chat to deliver to.
         */
        $target = $data['chat_id'];

        $result = $this->client->send($target, $data['text']);

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
            'from_number' => $data['number'] ?: $data['chat_id'],
            'contact_name' => null,
            'content' => $data['text'],
            'direction' => 'outgoing',
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'read_at' => Carbon::now(),
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
        $latestIds = DB::table('messaging_messages')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('chat_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return [];
        }

        $unreadCounts = DB::table('messaging_messages')
            ->select('chat_id', DB::raw('COUNT(*) as cnt'))
            ->where('direction', 'incoming')
            ->whereNull('read_at')
            ->groupBy('chat_id')
            ->pluck('cnt', 'chat_id');

        $leadIds = DB::table('messaging_messages')
            ->whereIn('chat_id', DB::table('messaging_messages')
                ->whereIn('id', $latestIds)
                ->pluck('chat_id'))
            ->whereNotNull('lead_id')
            ->select('chat_id', DB::raw('MAX(lead_id) as lead_id'))
            ->groupBy('chat_id')
            ->pluck('lead_id', 'chat_id');

        return DB::table('messaging_messages')
            ->whereIn('id', $latestIds)
            ->orderByDesc('id')
            ->get()
            ->map(function ($r) use ($unreadCounts, $leadIds) {
                $name = $this->displayName($r);
                $preview = $this->buildPreview($r);
                $lastAt = $r->sent_at ?: $r->created_at;

                return [
                    'chat_id' => $r->chat_id,
                    'name' => $name,
                    'initial' => $this->initialOf($name),
                    'phone' => $this->displayPhone($r),
                    'from_number' => $r->from_number,
                    'preview' => $preview,
                    'direction' => $r->direction,
                    'last_at' => $lastAt
                        ? Carbon::parse($lastAt)->toIso8601String()
                        : null,
                    'unread' => (int) ($unreadCounts[$r->chat_id] ?? 0),
                    'lead_id' => isset($leadIds[$r->chat_id]) ? (int) $leadIds[$r->chat_id] : null,
                ];
            })
            ->toArray();
    }

    protected function markChatAsRead(string $chatId): void
    {
        DB::table('messaging_messages')
            ->where('chat_id', $chatId)
            ->where('direction', 'incoming')
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    /**
     * Pushname first (if it carries at least one letter — pure dots/digits are
     * usually garbage from WhatsApp). For lid-only chats fall back to a
     * neutral label; for routable phone chats use the "+<digits>" form.
     */
    protected function displayName(object $r): string
    {
        $name = trim((string) ($r->contact_name ?? ''));

        if ($name !== '' && preg_match('/\p{L}/u', $name)) {
            return $name;
        }

        if ($this->isLidChat($r)) {
            return (string) trans('whatsapp::app.chat.anonymous');
        }

        return $this->displayPhone($r);
    }

    protected function isLidChat(object $r): bool
    {
        $chatId = (string) ($r->chat_id ?? '');
        $raw = (string) ($r->from_number ?? '');

        return str_contains($chatId, '@lid') || str_starts_with($raw, 'lid:');
    }

    protected function displayPhone(object $r): string
    {
        $raw = trim((string) ($r->from_number ?? ''));

        if ($raw === '' || ! preg_match('/[\p{L}\p{N}]/u', $raw)) {
            $raw = (string) ($r->chat_id ?? '');
        }

        if (str_starts_with($raw, 'lid:') || str_contains($raw, '@lid')) {
            return (string) trans('whatsapp::app.chat.anonymous');
        }

        if (str_contains($raw, '@')) {
            $digits = explode('@', $raw)[0];

            return $digits !== '' ? '+'.$digits : $raw;
        }

        if (preg_match('/^\d{7,}$/', $raw)) {
            return '+'.$raw;
        }

        return $raw;
    }

    protected function initialOf(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return '?';
        }

        if (preg_match('/\p{L}/u', $trimmed, $m)) {
            return mb_strtoupper($m[0]);
        }

        if (preg_match('/\d/u', $trimmed, $m)) {
            return $m[0];
        }

        return '?';
    }

    protected function buildPreview(object $r): string
    {
        $content = trim((string) ($r->content ?? ''));

        if ($content === '') {
            $content = '…';
        }

        if (mb_strlen($content) > 60) {
            $content = mb_substr($content, 0, 60).'…';
        }

        return $content;
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
