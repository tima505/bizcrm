<?php

namespace Webkul\Whatsapp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Whatsapp\Repositories\MessageRepository;

class IncomingController extends Controller
{
    public function __construct(
        protected MessageRepository $messages,
        protected PersonRepository $persons,
        protected LeadRepository $leads,
        protected SourceRepository $sources,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|string',
            'external_id' => 'nullable|string',
            'chat_id' => 'required|string',
            'from_number' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'contact_name' => 'nullable|string',
            'content' => 'required|string',
            'direction' => 'required|in:incoming,outgoing',
            'timestamp' => 'nullable|integer',
        ]);

        /**
         * Dedup by (channel, external_id). Baileys replays messages as
         * type=append after a reconnect, and the Node bridge now forwards
         * both notify + append events so nothing is lost during disconnect
         * windows. The side effect is that the same message id can arrive
         * twice; skip the second write and return success so Node does not
         * retry further.
         */
        if (! empty($data['external_id'])) {
            $existing = DB::table('messaging_messages')
                ->where('channel', $data['channel'])
                ->where('external_id', $data['external_id'])
                ->exists();

            if ($existing) {
                return response()->json(['ok' => true, 'deduped' => true]);
            }
        }

        $chatId = $data['chat_id'];
        $phone = $this->normalizePhone($data['phone_number'] ?? null);
        $display = $phone ?: $data['from_number'] ?? $chatId;

        /**
         * WhatsApp push-names come from the sender's own profile, so they
         * are frequently garbage ("." / "~" / an emoji only). Treat a name
         * as valid only if it contains at least one letter — otherwise fall
         * back to the phone number for both the person record and the lead
         * title.
         */
        $rawName = trim((string) ($data['contact_name'] ?? ''));
        $hasRealName = $rawName !== '' && preg_match('/\p{L}/u', $rawName);
        $contactName = $hasRealName ? $rawName : $display;

        [$personId, $leadId] = DB::transaction(function () use ($chatId, $phone, $display, $contactName, $hasRealName, $rawName) {
            $person = $this->findOrCreatePerson($chatId, $phone, $display, $contactName);

            $leadTitle = $hasRealName
                ? sprintf('%s (WhatsApp)', $rawName)
                : sprintf('Клиент %s', $display);

            $lead = $this->findOrCreateLead($person, $leadTitle);

            return [$person->id, $lead?->id];
        });

        $sentAt = isset($data['timestamp'])
            ? Carbon::createFromTimestamp($data['timestamp'])
            : Carbon::now();

        $this->messages->create([
            'channel' => $data['channel'],
            'external_id' => $data['external_id'] ?? null,
            'chat_id' => $chatId,
            'from_number' => $display,
            'contact_name' => $contactName,
            'content' => $data['content'],
            'direction' => $data['direction'],
            'status' => 'received',
            'sent_at' => $sentAt,
            'person_id' => $personId,
            'lead_id' => $leadId,
        ]);

        return response()->json(['ok' => true]);
    }

    protected function findOrCreatePerson(string $chatId, ?string $phone, string $display, string $name)
    {
        $model = $this->persons->getModel();

        $person = $model->whereJsonContains('contact_numbers', ['value' => $chatId])->first();

        if (! $person && $phone) {
            $person = $model->whereJsonContains('contact_numbers', ['value' => $phone])->first();
        }

        if ($person) {
            return $this->ensureNumberStored($person, $chatId, $phone);
        }

        $numbers = [['value' => $chatId, 'label' => 'whatsapp']];

        if ($phone && $phone !== $chatId) {
            $numbers[] = ['value' => $phone, 'label' => 'work'];
        }

        return $this->persons->create([
            'entity_type' => 'persons',
            'name' => $name,
            'emails' => [],
            'contact_numbers' => $numbers,
            'user_id' => $this->defaultOwnerId(),
        ]);
    }

    protected function ensureNumberStored($person, string $chatId, ?string $phone)
    {
        $numbers = $person->contact_numbers ?: [];
        $values = array_column($numbers, 'value');
        $changed = false;

        if (! in_array($chatId, $values, true)) {
            $numbers[] = ['value' => $chatId, 'label' => 'whatsapp'];
            $changed = true;
        }

        if ($phone && $phone !== $chatId && ! in_array($phone, $values, true)) {
            $numbers[] = ['value' => $phone, 'label' => 'work'];
            $changed = true;
        }

        if ($changed) {
            $person->contact_numbers = $numbers;
            $person->save();
        }

        return $person;
    }

    protected function findOrCreateLead($person, string $title)
    {
        $existing = $this->leads->getModel()
            ->where('person_id', $person->id)
            ->whereNull('closed_at')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->leads->create([
            'entity_type' => 'leads',
            'title' => $title,
            'description' => null,
            'lead_value' => 0,
            'status' => 1,
            'user_id' => $this->defaultOwnerId(),
            'person' => ['id' => $person->id],
            'lead_source_id' => $this->whatsappSourceId(),
            'lead_type_id' => 1,
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ]);
    }

    protected function whatsappSourceId(): int
    {
        $source = $this->sources->findOneWhere(['name' => 'WhatsApp']);

        if (! $source) {
            $source = $this->sources->create(['name' => 'WhatsApp']);
        }

        return (int) $source->id;
    }

    protected function defaultOwnerId(): ?int
    {
        return (int) (DB::table('users')->min('id') ?: 0) ?: null;
    }

    protected function normalizePhone(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        return $digits !== '' ? '+'.$digits : null;
    }
}
