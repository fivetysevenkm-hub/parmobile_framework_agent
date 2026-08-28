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

        $presetKey = (string) ($saved->preset_key ?? 'par-default');
        $presetKey = ($presetKey === 'custom' || array_key_exists($presetKey, $this->presets())) ? $presetKey : 'par-default';

        // Presets are the source of truth for every visual token, including table fills.
        // This also repairs older saved preferences that predate the table color tokens.
        $presetColors = $presetKey === 'custom'
            ? array_merge($defaults, $savedColors)
            : ($this->presets()[$presetKey]['colors'] ?? $defaults);

        foreach ($defaults as $key => $default) {
            $value = $presetColors[$key] ?? $default;
            $result['colors'][$key] = $this->isHexColor($value) ? strtoupper($value) : $default;
        }

        $result['preset_key'] = $presetKey;

        // Older custom preferences may have been saved before contrast validation
        // covered every surface. Do not render an unsafe combination at runtime.
        if ($this->contrastViolations($result['colors']) !== []) {
            $safeColors = $this->presets()['par-default']['colors'] ?? $defaults;
            foreach ($defaults as $key => $default) {
                $value = $safeColors[$key] ?? $default;
                $result['colors'][$key] = $this->isHexColor($value) ? strtoupper($value) : $default;
            }
            $result['preset_key'] = 'par-default';
        }

        return $result;
    }

    public function save(LegacyUser $user, string $presetKey, array $colors): void
    {
        $defaults = $this->defaults();
        $presets = $this->presets();
        $presetKey = ($presetKey === 'custom' || array_key_exists($presetKey, $presets)) ? $presetKey : 'par-default';
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

    public function contrastViolations(array $colors): array
    {
        $pairs = [
            ['text', 'bg', 'Teks fallback vs latar halaman'],
            ['text_body', 'bg', 'Teks body vs latar halaman'],
            ['text_body', 'panel', 'Teks body vs panel'],
            ['text_body', 'panel_2', 'Teks body vs panel sekunder'],
            ['text_body', 'filter_bg', 'Teks body vs area filter'],
            ['text_body', 'input_bg', 'Teks input vs latar input'],
            ['text_body', 'input_focus_bg', 'Teks body vs input saat fokus'],
            ['text_title', 'bg', 'Teks judul vs latar halaman'],
            ['text_title', 'panel', 'Teks judul vs panel'],
            ['text_title', 'panel_2', 'Teks judul vs panel sekunder'],
            ['text_title', 'table_row', 'Teks judul vs baris tabel'],
            ['text_table', 'table_row', 'Teks tabel vs baris tabel'],
            ['text_table', 'table_alt', 'Teks tabel vs baris tabel selang-seling'],
            ['text_table', 'table_header', 'Teks tabel vs header tabel'],
            ['text_table', 'table_sticky', 'Teks tabel vs kolom tabel tetap'],
            ['input_text', 'input_bg', 'Teks input vs latar input'],
            ['input_text', 'input_focus_bg', 'Teks input vs latar fokus'],
            ['input_placeholder', 'input_bg', 'Placeholder vs latar input'],
            ['text_button', 'button_primary', 'Teks tombol vs tombol utama'],
            ['button_secondary_text', 'button_secondary_bg', 'Teks tombol sekunder vs latarnya'],
            ['button_disabled_text', 'button_disabled_bg', 'Teks tombol nonaktif vs latarnya'],
            ['text_header', 'navbar_bg', 'Teks header vs navbar'],
            ['navbar_icon', 'navbar_bg', 'Ikon navbar vs latar navbar'],
            ['navbar_icon', 'navbar_icon_hover_bg', 'Ikon navbar vs latar hover'],
            ['logout', 'navbar_bg', 'Tombol logout vs navbar'],
            ['text_sidebar', 'sidebar_bg', 'Teks sidebar vs latar sidebar'],
            ['text_header', 'sidebar_active_bg', 'Teks menu aktif vs latarnya'],
            ['text_footer', 'bg', 'Teks footer vs latar halaman'],
            ['text_footer', 'panel', 'Teks footer vs panel'],
            ['modal_text', 'modal_bg', 'Teks modal vs latarnya'],
            ['modal_heading', 'modal_bg', 'Judul modal vs latarnya'],
            ['modal_copy', 'modal_bg', 'Keterangan modal vs latarnya'],
            ['modal_icon_text', 'modal_icon_bg', 'Teks ikon modal vs latarnya'],
            ['success', 'success_bg', 'Teks status berhasil vs latarnya'],
            ['warning', 'warning_bg', 'Teks status peringatan vs latarnya'],
            ['danger', 'danger_bg', 'Teks status bahaya vs latarnya'],
            ['info', 'info_bg', 'Teks status informasi vs latarnya'],
        ];

        $violations = [];
        foreach ($pairs as [$foreground, $background, $label]) {
            $ratio = $this->contrastRatio($colors[$foreground] ?? null, $colors[$background] ?? null);
            if ($ratio !== null && $ratio < 4.5) {
                $violations[] = $label.' hanya memiliki kontras '.number_format($ratio, 2).' : 1. Minimum yang disarankan adalah 4.5 : 1.';
            }
        }

        return $violations;
    }

    public function contrastRatio(?string $foreground, ?string $background): ?float
    {
        if (! $this->isHexColor($foreground) || ! $this->isHexColor($background)) {
            return null;
        }

        $relative = static function (string $hex): float {
            $rgb = array_map(static fn (string $part): float => hexdec($part) / 255, str_split(substr($hex, 1), 2));
            $linear = array_map(static fn (float $channel): float => $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4, $rgb);
            return (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);
        };

        $light = max($relative($foreground), $relative($background));
        $dark = min($relative($foreground), $relative($background));

        return ($light + 0.05) / ($dark + 0.05);
    }

    public function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }
}
