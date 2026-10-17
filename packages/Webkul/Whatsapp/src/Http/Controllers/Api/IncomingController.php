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
            'contact_name' => 'nullable|string',
            'content' => 'required|string',
            'direction' => 'required|in:incoming,outgoing',
            'timestamp' => 'nullable|integer',
        ]);

        $fromNumber = $this->normalizeNumber($data['from_number'] ?? $data['chat_id']);
        $contactName = $data['contact_name'] ?: $fromNumber;

        [$personId, $leadId] = DB::transaction(function () use ($fromNumber, $contactName) {
            $person = $this->findOrCreatePerson($fromNumber, $contactName);
            $lead = $this->findOrCreateLead($person, $fromNumber);

            return [$person->id, $lead?->id];
        });

        $sentAt = isset($data['timestamp'])
            ? Carbon::createFromTimestamp($data['timestamp'])
            : Carbon::now();

        $this->messages->create([
            'channel' => $data['channel'],
            'external_id' => $data['external_id'] ?? null,
            'chat_id' => $data['chat_id'],
            'from_number' => $fromNumber,
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

    protected function findOrCreatePerson(string $number, string $name)
    {
        $person = $this->persons->getModel()
            ->whereJsonContains('contact_numbers', ['value' => $number])
            ->first();

        if ($person) {
            return $person;
        }

        return $this->persons->create([
            'entity_type' => 'persons',
            'name' => $name,
            'emails' => [],
            'contact_numbers' => [['value' => $number, 'label' => 'work']],
            'user_id' => $this->defaultOwnerId(),
        ]);
    }

    protected function findOrCreateLead($person, string $number)
    {
        $existing = $this->leads->getModel()
            ->where('person_id', $person->id)
            ->whereNull('closed_at')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $sourceId = $this->whatsappSourceId();

        return $this->leads->create([
            'title' => 'WhatsApp: '.$number,
            'description' => null,
            'lead_value' => 0,
            'status' => 1,
            'user_id' => $this->defaultOwnerId(),
            'person' => ['id' => $person->id],
            'lead_source_id' => $sourceId,
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

    protected function normalizeNumber(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        return $digits !== '' ? '+'.$digits : $raw;
    }
}
