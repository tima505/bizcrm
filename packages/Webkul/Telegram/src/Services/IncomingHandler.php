<?php

namespace Webkul\Telegram\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Telegram\Models\Message;

/**
 * Shared handler for incoming Telegram updates. Used both by the webhook
 * controller (production) and the long-polling artisan command (dev).
 *
 * The expected input is a decoded `Update` object as described in
 * https://core.telegram.org/bots/api#update — only `message` updates with a
 * `text` payload are processed, everything else is skipped silently so a
 * sticker or edited message does not blow up the dedup window.
 */
class IncomingHandler
{
    public function __construct(
        protected PersonRepository $persons,
        protected LeadRepository $leads,
        protected SourceRepository $sources,
    ) {}

    /**
     * @return array{status: 'ok'|'skipped'|'deduped', message_id?: int}
     */
    public function handle(array $update): array
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return ['status' => 'skipped'];
        }

        $text = (string) ($message['text'] ?? '');

        if ($text === '') {
            return ['status' => 'skipped'];
        }

        $chatId = isset($message['chat']['id']) ? (string) $message['chat']['id'] : null;
        $messageId = isset($message['message_id']) ? (string) $message['message_id'] : null;

        if (! $chatId || ! $messageId) {
            return ['status' => 'skipped'];
        }

        $externalId = $chatId.':'.$messageId;

        $existing = DB::table('messaging_messages')
            ->where('channel', 'telegram')
            ->where('external_id', $externalId)
            ->exists();

        if ($existing) {
            return ['status' => 'deduped'];
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];

        $username = (string) ($from['username'] ?? $chat['username'] ?? '');
        $firstName = trim((string) ($from['first_name'] ?? $chat['first_name'] ?? ''));
        $lastName = trim((string) ($from['last_name'] ?? $chat['last_name'] ?? ''));

        /**
         * Treat the pushname as valid only if it actually contains a letter.
         * Some Telegram profiles have first_name set to "." or an emoji only,
         * which would otherwise end up as the contact label everywhere.
         */
        $rawName = trim($firstName.' '.$lastName);
        $hasLetterName = $rawName !== '' && preg_match('/\p{L}/u', $rawName);

        if ($hasLetterName) {
            $displayName = $rawName;
        } elseif ($username !== '') {
            $displayName = '@'.$username;
        } else {
            $displayName = (string) trans('telegram::app.chat.anonymous');
        }

        $leadTitle = $this->buildLeadTitle($firstName, $lastName, $username, $chatId);

        [$personId, $leadId] = DB::transaction(function () use ($chatId, $username, $displayName, $leadTitle) {
            $person = $this->findOrCreatePerson($chatId, $username, $displayName);
            $lead = $this->findOrCreateLead($person, $leadTitle);

            return [$person->id, $lead?->id];
        });

        $sentAt = isset($message['date'])
            ? Carbon::createFromTimestamp((int) $message['date'])
            : Carbon::now();

        $row = Message::create([
            'channel' => 'telegram',
            'external_id' => $externalId,
            'chat_id' => $chatId,
            'from_number' => $username !== '' ? '@'.$username : null,
            'contact_name' => $displayName,
            'content' => $text,
            'direction' => 'incoming',
            'status' => 'received',
            'sent_at' => $sentAt,
            'person_id' => $personId,
            'lead_id' => $leadId,
        ]);

        return ['status' => 'ok', 'message_id' => (int) $row->id];
    }

    protected function buildLeadTitle(string $first, string $last, string $username, string $chatId): string
    {
        $name = trim($first.' '.$last);

        if ($name !== '' && preg_match('/\p{L}/u', $name)) {
            return $name.' (Telegram)';
        }

        if ($username !== '') {
            return '@'.$username.' (Telegram)';
        }

        return (string) trans('telegram::app.chat.anonymous').' #'.$chatId;
    }

    protected function findOrCreatePerson(string $chatId, string $username, string $displayName)
    {
        $model = $this->persons->getModel();

        $tgKey = 'tg:'.$chatId;

        $person = $model->whereJsonContains('contact_numbers', ['value' => $tgKey])->first();

        if ($person) {
            return $this->ensureNumberStored($person, $tgKey, $username);
        }

        $numbers = [['value' => $tgKey, 'label' => 'telegram']];

        if ($username !== '') {
            $numbers[] = ['value' => '@'.$username, 'label' => 'telegram'];
        }

        return $this->persons->create([
            'entity_type' => 'persons',
            'name' => $displayName,
            'emails' => [],
            'contact_numbers' => $numbers,
            'user_id' => $this->defaultOwnerId(),
        ]);
    }

    protected function ensureNumberStored($person, string $tgKey, string $username)
    {
        $numbers = $person->contact_numbers ?: [];
        $values = array_column($numbers, 'value');
        $changed = false;

        if (! in_array($tgKey, $values, true)) {
            $numbers[] = ['value' => $tgKey, 'label' => 'telegram'];
            $changed = true;
        }

        if ($username !== '' && ! in_array('@'.$username, $values, true)) {
            $numbers[] = ['value' => '@'.$username, 'label' => 'telegram'];
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
            'lead_source_id' => $this->telegramSourceId(),
            'lead_type_id' => 1,
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ]);
    }

    protected function telegramSourceId(): int
    {
        $source = $this->sources->findOneWhere(['name' => 'Telegram']);

        if (! $source) {
            $source = $this->sources->create(['name' => 'Telegram']);
        }

        return (int) $source->id;
    }

    protected function defaultOwnerId(): ?int
    {
        return (int) (DB::table('users')->min('id') ?: 0) ?: null;
    }
}
