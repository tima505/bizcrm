<?php

namespace Webkul\Telegram\Repositories;

use Illuminate\Support\Facades\Schema;
use Webkul\Telegram\Models\ChannelIntegration;

class IntegrationRepository
{
    public function get(string $channel): ?ChannelIntegration
    {
        if (! Schema::hasTable('channel_integrations')) {
            return null;
        }

        return ChannelIntegration::query()->where('channel', $channel)->first();
    }

    public function save(string $channel, array $settings, array $meta = []): ChannelIntegration
    {
        $row = ChannelIntegration::firstOrNew(['channel' => $channel]);

        $row->settings = $settings;

        foreach (['enabled', 'display_name', 'username', 'external_id', 'verified_at'] as $k) {
            if (array_key_exists($k, $meta)) {
                $row->{$k} = $meta[$k];
            }
        }

        $row->save();

        return $row;
    }

    public function disable(string $channel): void
    {
        $row = $this->get($channel);

        if (! $row) {
            return;
        }

        $row->enabled = false;
        $row->save();
    }

    public function forget(string $channel): void
    {
        $row = $this->get($channel);

        if ($row) {
            $row->delete();
        }
    }
}
