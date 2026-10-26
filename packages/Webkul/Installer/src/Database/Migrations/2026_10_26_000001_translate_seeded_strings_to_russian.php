<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites the user-facing strings that the Krayin installer seeders
 * dropped in English (pipelines, stages, types, sources, admin user,
 * role, attribute labels, email-template titles) to Russian. Lookups
 * are keyed by stable columns (code, entity_type, id=1, current
 * English value), so the migration is idempotent on fresh DBs that
 * already have Russian seeded via the installer lang file.
 *
 * The admin role name intentionally becomes "Администратор" too —
 * surfaced in Settings → Users → Roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        /**
         * Pipelines + stages. Stage lookups use code because id ordering is
         * stable but code is semantic.
         */
        DB::table('lead_pipelines')
            ->where('name', 'Default Pipeline')
            ->update(['name' => 'Основная воронка']);

        $stages = [
            'new' => 'Новый',
            'follow-up' => 'Связались',
            'prospect' => 'Потенциальный',
            'negotiation' => 'Переговоры',
            'won' => 'Успешно',
            'lost' => 'Провал',
        ];
        foreach ($stages as $code => $name) {
            DB::table('lead_pipeline_stages')
                ->where('code', $code)
                ->update(['name' => $name]);
        }

        /**
         * Lead types.
         */
        $types = [
            'New Business' => 'Новый клиент',
            'Existing Business' => 'Повторный клиент',
        ];
        foreach ($types as $en => $ru) {
            DB::table('lead_types')->where('name', $en)->update(['name' => $ru]);
        }

        /**
         * Lead sources. "WhatsApp" stays English because that's the
         * canonical brand name.
         */
        $sources = [
            'Email' => 'Email',
            'Web' => 'Сайт',
            'Web Form' => 'Веб-форма',
            'Phone' => 'Телефон',
            'Direct' => 'Прямой контакт',
        ];
        foreach ($sources as $en => $ru) {
            DB::table('lead_sources')->where('name', $en)->update(['name' => $ru]);
        }

        /**
         * Admin user name — reached via "Example" set by the pre-i18n
         * installer. Email guards against accidental hits on a renamed
         * production admin.
         */
        DB::table('users')
            ->where('id', 1)
            ->where('email', 'admin@example.com')
            ->whereIn('name', ['Example', 'Example Admin'])
            ->update(['name' => 'Администратор']);

        /**
         * Administrator role (shown in Settings → Users → Roles).
         */
        DB::table('roles')
            ->where('id', 1)
            ->where('name', 'Administrator')
            ->update([
                'name' => 'Администратор',
                'description' => 'Роль администратора',
            ]);

        /**
         * Attribute labels. Keyed on (entity_type, code) so this still
         * works if ids shift. All translations mirror the installer
         * lang file (installer/lang/ru/app.php → seeders.attributes.*).
         */
        $attrs = [
            ['leads', 'title', 'Заголовок'],
            ['leads', 'description', 'Описание'],
            ['leads', 'lead_value', 'Оценочная стоимость лида'],
            ['leads', 'lead_source_id', 'Источник'],
            ['leads', 'lead_type_id', 'Тип'],
            ['leads', 'user_id', 'Ответственный'],
            ['leads', 'expected_close_date', 'Ожидаемая дата закрытия'],
            ['leads', 'lead_pipeline_id', 'Воронка'],
            ['leads', 'lead_pipeline_stage_id', 'Этап'],

            ['persons', 'name', 'Имя'],
            ['persons', 'emails', 'Электронные адреса'],
            ['persons', 'contact_numbers', 'Контактные телефоны'],
            ['persons', 'job_title', 'Должность'],
            ['persons', 'user_id', 'Ответственный'],
            ['persons', 'organization_id', 'Организация'],

            ['organizations', 'name', 'Название'],
            ['organizations', 'address', 'Адрес'],
            ['organizations', 'user_id', 'Ответственный'],

            ['products', 'name', 'Название'],
            ['products', 'description', 'Описание'],
            ['products', 'sku', 'Артикул'],
            ['products', 'quantity', 'Количество'],
            ['products', 'price', 'Цена'],

            ['quotes', 'user_id', 'Ответственный'],
            ['quotes', 'subject', 'Тема'],
            ['quotes', 'description', 'Описание'],
            ['quotes', 'billing_address', 'Адрес для счёта'],
            ['quotes', 'shipping_address', 'Адрес доставки'],
            ['quotes', 'discount_percent', 'Скидка (%)'],
            ['quotes', 'discount_amount', 'Сумма скидки'],
            ['quotes', 'tax_amount', 'Сумма налога'],
            ['quotes', 'adjustment_amount', 'Корректировка'],
            ['quotes', 'sub_total', 'Подытог'],
            ['quotes', 'grand_total', 'Итого'],
            ['quotes', 'person_id', 'Контактное лицо'],
            ['quotes', 'expired_at', 'Срок действия'],

            ['warehouses', 'name', 'Название'],
            ['warehouses', 'description', 'Описание'],
            ['warehouses', 'contact_name', 'Контактное лицо'],
            ['warehouses', 'contact_emails', 'Контактные email'],
            ['warehouses', 'contact_numbers', 'Контактные телефоны'],
            ['warehouses', 'contact_address', 'Контактный адрес'],
        ];
        foreach ($attrs as [$entity, $code, $name]) {
            DB::table('attributes')
                ->where('entity_type', $entity)
                ->where('code', $code)
                ->update(['name' => $name]);
        }

        /**
         * Email templates — only the human-facing name and subject.
         * The HTML body still contains the English table-cell labels
         * (`<td>Title</td>` etc.) — leaving that for a dedicated pass.
         */
        DB::table('email_templates')
            ->where('id', 1)
            ->where('name', 'Activity created')
            ->update([
                'name' => 'Активность создана',
                'subject' => 'Активность создана: {%activities.title%}',
            ]);
        DB::table('email_templates')
            ->where('id', 2)
            ->where('name', 'Activity modified')
            ->update([
                'name' => 'Активность изменена',
                'subject' => 'Активность изменена: {%activities.title%}',
            ]);
    }

    public function down(): void
    {
        DB::table('lead_pipelines')
            ->where('name', 'Основная воронка')
            ->update(['name' => 'Default Pipeline']);

        $stages = [
            'new' => 'New',
            'follow-up' => 'Follow Up',
            'prospect' => 'Prospect',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
        foreach ($stages as $code => $name) {
            DB::table('lead_pipeline_stages')->where('code', $code)->update(['name' => $name]);
        }

        $types = [
            'Новый клиент' => 'New Business',
            'Повторный клиент' => 'Existing Business',
        ];
        foreach ($types as $ru => $en) {
            DB::table('lead_types')->where('name', $ru)->update(['name' => $en]);
        }

        $sources = [
            'Сайт' => 'Web',
            'Веб-форма' => 'Web Form',
            'Телефон' => 'Phone',
            'Прямой контакт' => 'Direct',
        ];
        foreach ($sources as $ru => $en) {
            DB::table('lead_sources')->where('name', $ru)->update(['name' => $en]);
        }

        DB::table('users')
            ->where('id', 1)
            ->where('email', 'admin@example.com')
            ->where('name', 'Администратор')
            ->update(['name' => 'Example']);

        DB::table('roles')
            ->where('id', 1)
            ->where('name', 'Администратор')
            ->update([
                'name' => 'Administrator',
                'description' => 'Administrator Role',
            ]);

        // Attribute labels: reverse map.
        $attrs = [
            ['leads', 'title', 'Title'],
            ['leads', 'description', 'Description'],
            ['leads', 'lead_value', 'Estimated Lead Value'],
            ['leads', 'lead_source_id', 'Source'],
            ['leads', 'lead_type_id', 'Type'],
            ['leads', 'user_id', 'Sales Owner'],
            ['leads', 'expected_close_date', 'Expected Close Date'],
            ['leads', 'lead_pipeline_id', 'Pipeline'],
            ['leads', 'lead_pipeline_stage_id', 'Stage'],
            ['persons', 'name', 'Name'],
            ['persons', 'emails', 'Emails'],
            ['persons', 'contact_numbers', 'Contact Numbers'],
            ['persons', 'job_title', 'Job Title'],
            ['persons', 'user_id', 'Sales Owner'],
            ['persons', 'organization_id', 'Organization'],
            ['organizations', 'name', 'Name'],
            ['organizations', 'address', 'Address'],
            ['organizations', 'user_id', 'Sales Owner'],
            ['products', 'name', 'Name'],
            ['products', 'description', 'Description'],
            ['products', 'sku', 'SKU'],
            ['products', 'quantity', 'Quantity'],
            ['products', 'price', 'Price'],
            ['quotes', 'user_id', 'Sales Owner'],
            ['quotes', 'subject', 'Subject'],
            ['quotes', 'description', 'Description'],
            ['quotes', 'billing_address', 'Billing Address'],
            ['quotes', 'shipping_address', 'Shipping Address'],
            ['quotes', 'discount_percent', 'Discount Percent'],
            ['quotes', 'discount_amount', 'Discount Amount'],
            ['quotes', 'tax_amount', 'Tax Amount'],
            ['quotes', 'adjustment_amount', 'Adjustment Amount'],
            ['quotes', 'sub_total', 'Sub Total'],
            ['quotes', 'grand_total', 'Grand Total'],
            ['quotes', 'person_id', 'Person'],
            ['quotes', 'expired_at', 'Expired At'],
            ['warehouses', 'name', 'Name'],
            ['warehouses', 'description', 'Description'],
            ['warehouses', 'contact_name', 'Contact Name'],
            ['warehouses', 'contact_emails', 'Contact Emails'],
            ['warehouses', 'contact_numbers', 'Contact Numbers'],
            ['warehouses', 'contact_address', 'Contact Address'],
        ];
        foreach ($attrs as [$entity, $code, $name]) {
            DB::table('attributes')
                ->where('entity_type', $entity)
                ->where('code', $code)
                ->update(['name' => $name]);
        }

        DB::table('email_templates')->where('id', 1)->update([
            'name' => 'Activity created',
            'subject' => 'Activity created: {%activities.title%}',
        ]);
        DB::table('email_templates')->where('id', 2)->update([
            'name' => 'Activity modified',
            'subject' => 'Activity modified: {%activities.title%}',
        ]);
    }
};
