<?php

namespace App\Livewire;

use App\Services\ThemePreferenceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomTheme extends Component
{
    public string $presetKey = 'par-default';

    public array $colors = [];

    public array $presetNames = [];

    public array $contrastWarnings = [];

    public function mount(ThemePreferenceService $themes): void
    {
        $saved = $themes->currentForUser(Auth::user());
        $this->presetKey = $saved['preset_key'];
        $this->colors = $saved['colors'];
        $this->presetNames = collect($themes->presets())
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['name']])
            ->all();
        $this->contrastWarnings = $themes->contrastViolations($this->colors);
    }

    public function applyPreset(string $presetKey, ThemePreferenceService $themes): void
    {
        $preset = $themes->presets()[$presetKey] ?? null;
        if (! $preset) {
            return;
        }

        $this->presetKey = $presetKey;
        $this->colors = $preset['colors'];
        $this->contrastWarnings = $themes->contrastViolations($this->colors);
        $this->resetErrorBag();
    }

    public function updatedColors(): void
    {
        $this->presetKey = 'custom';
        $this->contrastWarnings = app(ThemePreferenceService::class)->contrastViolations($this->colors);
    }

    public function save(ThemePreferenceService $themes): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        foreach ($themes->defaults() as $key => $default) {
            if (! $themes->isHexColor($this->colors[$key] ?? null)) {
                $this->addError('colors.'.$key, 'Gunakan format warna HEX, contoh #00E5FF.');
            }
        }

        if ($this->getErrorBag()->count() > 0) {
            return;
        }

        $this->contrastWarnings = $themes->contrastViolations($this->colors);
        if ($this->contrastWarnings !== []) {
            $this->addError('contrast', 'Perbaiki kombinasi warna yang kontrasnya terlalu rendah sebelum menyimpan.');
            return;
        }

        $themes->save($user, $this->presetKey, $this->colors);
        session()->flash('success', 'Tema berhasil disimpan untuk akun Anda.');
        $this->dispatch('theme-saved');
    }

    public function resetToDefault(ThemePreferenceService $themes): void
    {
        $this->presetKey = 'par-default';
        $this->colors = $themes->defaults();
        $this->contrastWarnings = $themes->contrastViolations($this->colors);
        $this->resetErrorBag();
    }

    public function render(ThemePreferenceService $themes)
    {
        $labels = [
            'bg' => 'Latar halaman',
            'panel' => 'Panel utama',
            'panel_2' => 'Panel sekunder / area gelap',
            'line' => 'Border dan garis umum',
            'navbar_bg' => 'Latar navbar',
            'sidebar_bg' => 'Latar sidebar',
            'sidebar_active_bg' => 'Latar menu aktif / hover',
            'sidebar_active_indicator' => 'Indikator menu aktif',
            'filter_bg' => 'Area filter',
            'input_bg' => 'Latar input dan select',
            'input_focus_bg' => 'Latar input saat fokus',
            'input_text' => 'Teks input',
            'input_placeholder' => 'Placeholder input',
            'table_header' => 'Fill header tabel',
            'table_alt' => 'Fill baris tabel selang-seling',
            'table_row' => 'Fill baris tabel utama',
            'table_hover' => 'Fill baris tabel saat hover',
            'table_border' => 'Border tabel',
            'table_sticky' => 'Fill kolom tabel tetap',
            'cyan' => 'Aksen utama',
            'orange' => 'Aksen peringatan',
            'red' => 'Aksen bahaya',
            'lime' => 'Aksen lime',
            'success' => 'Teks status berhasil',
            'success_bg' => 'Fill status berhasil',
            'warning' => 'Teks status peringatan',
            'warning_bg' => 'Fill status peringatan',
            'danger' => 'Teks status bahaya',
            'danger_bg' => 'Fill status bahaya',
            'info' => 'Teks status informasi',
            'info_bg' => 'Fill status informasi',
            'text' => 'Teks fallback umum',
            'text_sidebar' => 'Teks sidebar',
            'text_header' => 'Teks header / navbar',
            'text_title' => 'Teks judul',
            'text_table' => 'Teks isi tabel',
            'text_button' => 'Teks tombol utama',
            'text_footer' => 'Teks footer / keterangan',
            'text_body' => 'Teks paragraf body',
            'button_primary' => 'Fill tombol utama',
            'button_primary_hover' => 'Fill tombol utama saat hover',
            'button_secondary_bg' => 'Fill tombol sekunder',
            'button_secondary_text' => 'Teks tombol sekunder',
            'button_disabled_bg' => 'Fill tombol disabled',
            'button_disabled_text' => 'Teks tombol disabled',
            'focus_ring' => 'Ring fokus keyboard',
            'navbar_icon' => 'Ikon navbar',
            'navbar_icon_hover_bg' => 'Fill ikon navbar saat hover',
            'logout' => 'Teks tombol logout',
            'modal_backdrop' => 'Overlay modal',
            'modal_bg' => 'Fill modal',
            'modal_border' => 'Border modal',
            'modal_text' => 'Teks modal',
            'modal_heading' => 'Judul modal',
            'modal_copy' => 'Keterangan modal',
            'modal_icon_bg' => 'Fill ikon modal',
            'modal_icon_text' => 'Teks ikon modal',
            'logo_bg' => 'Fill logo',
            'shadow_color' => 'Warna bayangan',
            'chart_grid' => 'Grid chart',
            'chart_label' => 'Label chart',
            'chart_border' => 'Border chart',
            'chart_1' => 'Palet chart 1',
            'chart_2' => 'Palet chart 2',
            'chart_3' => 'Palet chart 3',
            'chart_4' => 'Palet chart 4',
            'chart_5' => 'Palet chart 5',
            'chart_6' => 'Palet chart 6',
            'chart_7' => 'Palet chart 7',
            'chart_8' => 'Palet chart 8',
            'chart_9' => 'Palet chart 9',
            'chart_10' => 'Palet chart 10',
        ];

        $coreKeys = array_keys($labels);
        $legacyKeys = collect($themes->defaults())
            ->keys()
            ->filter(fn (string $key): bool => str_starts_with($key, 'legacy_'))
            ->values()
            ->all();

        $labels = array_merge($labels, collect($legacyKeys)->mapWithKeys(fn (string $key): array => [
            $key => 'Warna kompatibilitas halaman ('.strtoupper(substr($key, 7)).')',
        ])->all());

        return view('livewire.custom-theme', [
            'colorLabels' => $labels,
            'colorGroups' => [
                ['name' => 'Latar & Permukaan', 'keys' => ['bg', 'panel', 'panel_2', 'line', 'navbar_bg', 'sidebar_bg', 'sidebar_active_bg', 'sidebar_active_indicator', 'filter_bg', 'input_bg', 'input_focus_bg']],
                ['name' => 'Tabel & Form', 'keys' => ['input_text', 'input_placeholder', 'table_header', 'table_alt', 'table_row', 'table_hover', 'table_border', 'table_sticky']],
                ['name' => 'Aksen & Status', 'keys' => ['cyan', 'orange', 'red', 'lime', 'success', 'success_bg', 'warning', 'warning_bg', 'danger', 'danger_bg', 'info', 'info_bg']],
                ['name' => 'Semua Warna Text', 'keys' => ['text', 'text_sidebar', 'text_header', 'text_title', 'text_table', 'text_button', 'text_footer', 'text_body']],
                ['name' => 'Tombol, Fokus & Navigasi', 'keys' => ['button_primary', 'button_primary_hover', 'button_secondary_bg', 'button_secondary_text', 'button_disabled_bg', 'button_disabled_text', 'focus_ring', 'navbar_icon', 'navbar_icon_hover_bg', 'logout']],
                ['name' => 'Modal, Logo & Efek', 'keys' => ['modal_backdrop', 'modal_bg', 'modal_border', 'modal_text', 'modal_heading', 'modal_copy', 'modal_icon_bg', 'modal_icon_text', 'logo_bg', 'shadow_color']],
                ['name' => 'Chart & Visualisasi', 'keys' => ['chart_grid', 'chart_label', 'chart_border', 'chart_1', 'chart_2', 'chart_3', 'chart_4', 'chart_5', 'chart_6', 'chart_7', 'chart_8', 'chart_9', 'chart_10']],
                ['name' => 'Elemen Halaman Tambahan', 'keys' => $legacyKeys],
            ],
            'presetNames' => $this->presetNames ?: collect($themes->presets())->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['name']])->all(),
            'presets' => $themes->presets(),
            'contrastWarnings' => $this->contrastWarnings,
        ])->layout('layouts.app');
    }
}
