<?php

namespace App\Services;

use App\Models\LegacyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThemePreferenceService
{
    public function defaults(): array
    {
        return config('theme.defaults', []);
    }

    public function presets(): array
    {
        return config('theme.presets', []);
    }

    public function currentForUser(?LegacyUser $user): array
    {
        $defaults = $this->defaults();
        $result = [
            'preset_key' => 'par-default',
            'colors' => $defaults,
        ];

        if (! $user) {
            return $result;
        }

        $idUser = (string) $user->getAuthIdentifier();
        if ($idUser === '') {
            return $result;
        }

        if (! Schema::hasTable('tbl_user_theme_preferences')) {
            return $result;
        }

        $saved = DB::table('tbl_user_theme_preferences')
            ->where('id_user', $idUser)
            ->first(['preset_key', 'colors_json']);

        if (! $saved) {
            return $result;
        }

        $savedColors = json_decode((string) ($saved->colors_json ?? ''), true);
        $savedColors = is_array($savedColors) ? $savedColors : [];

        foreach ($defaults as $key => $default) {
            $value = $savedColors[$key] ?? $default;
            $result['colors'][$key] = $this->isHexColor($value) ? strtoupper($value) : $default;
        }

        $presetKey = (string) ($saved->preset_key ?? 'par-default');
        $result['preset_key'] = array_key_exists($presetKey, $this->presets()) ? $presetKey : 'par-default';

        return $result;
    }

    public function save(LegacyUser $user, string $presetKey, array $colors): void
    {
        $defaults = $this->defaults();
        $presets = $this->presets();
        $presetKey = array_key_exists($presetKey, $presets) ? $presetKey : 'par-default';
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $value = $colors[$key] ?? $default;
            $normalized[$key] = $this->isHexColor($value) ? strtoupper($value) : $default;
        }

        $now = now();
        DB::table('tbl_user_theme_preferences')->updateOrInsert(
            ['id_user' => (string) $user->getAuthIdentifier()],
            [
                'preset_key' => $presetKey,
                'colors_json' => json_encode($normalized, JSON_THROW_ON_ERROR),
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    public function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }
}
