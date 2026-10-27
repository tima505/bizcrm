<?php

namespace Webkul\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Webkul\Telegram\Contracts\ChannelIntegration as ChannelIntegrationContract;

/**
 * Per-channel integration settings. The row for `channel = 'telegram'` holds
 * the encrypted bot token plus denormalised display fields that come from
 * Bot API `getMe` so the UI can show "connected as @my_bot" without decrypting
 * the token on every page view.
 */
class ChannelIntegration extends Model implements ChannelIntegrationContract
{
    protected $table = 'channel_integrations';

    protected $fillable = [
        'channel',
        'enabled',
        'settings',
        'display_name',
        'username',
        'external_id',
        'verified_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function getSettingsAttribute($value): array
    {
        if (! $value) {
            return [];
        }

        try {
            $json = Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setSettingsAttribute($value): void
    {
        if ($value === null || $value === []) {
            $this->attributes['settings'] = null;

            return;
        }

        $this->attributes['settings'] = Crypt::encryptString(json_encode($value));
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
