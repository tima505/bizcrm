<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off: convert WhatsApp lead titles written by the pre-i18n
 * IncomingController ("WhatsApp: +7706...") to the current scheme:
 *   - "<Name> (WhatsApp)" if the linked person has a letter-containing
 *     name (ignoring push-names that are "." / "~" / emoji-only);
 *   - "Клиент <phone>" if we have a routable phone;
 *   - "Клиент WhatsApp" for lid-only chats without a disclosed phone.
 *
 * The title lives in BOTH leads.title (for datagrid/kanban queries) and
 * attribute_values.text_value for attribute leads.title; both must be
 * updated in lockstep to keep the record and its EAV mirror in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        $titleAttributeId = DB::table('attributes')
            ->where('entity_type', 'leads')
            ->where('code', 'title')
            ->value('id');

        $leads = DB::table('leads')
            ->where('title', 'like', 'WhatsApp:%')
            ->get(['id', 'title', 'person_id']);

        foreach ($leads as $lead) {
            $newTitle = $this->newTitleFor($lead);

            if ($newTitle === null || $newTitle === $lead->title) {
                continue;
            }

            DB::table('leads')
                ->where('id', $lead->id)
                ->update(['title' => $newTitle]);

            if ($titleAttributeId) {
                DB::table('attribute_values')
                    ->where('entity_type', 'leads')
                    ->where('entity_id', $lead->id)
                    ->where('attribute_id', $titleAttributeId)
                    ->update(['text_value' => $newTitle]);
            }
        }
    }

    public function down(): void
    {
        // No reverse — we don't preserve the pre-migration title and
        // regenerating "WhatsApp: $display" would need person lookups
        // again. If a rollback is ever needed, use the pre-i18n backup
        // in storage/backups/.
    }

    protected function newTitleFor(object $lead): ?string
    {
        if (! $lead->person_id) {
            return null;
        }

        $person = DB::table('persons')
            ->where('id', $lead->person_id)
            ->first(['name', 'contact_numbers']);

        if (! $person) {
            return null;
        }

        $name = trim((string) ($person->name ?? ''));

        if ($name !== '' && preg_match('/\p{L}/u', $name)) {
            return $name.' (WhatsApp)';
        }

        $numbers = json_decode((string) ($person->contact_numbers ?? '[]'), true) ?: [];
        $phone = null;

        foreach ($numbers as $entry) {
            $value = (string) ($entry['value'] ?? '');
            if ($value === '') {
                continue;
            }
            if (str_contains($value, '@lid') || str_starts_with($value, 'lid:')) {
                continue;
            }
            // Prefer the first phone-shaped entry: digits with optional +.
            if (preg_match('/^\+?\d{7,}$/', $value)) {
                $phone = $value;
                break;
            }
        }

        return $phone
            ? 'Клиент '.$phone
            : 'Клиент WhatsApp';
    }
};
