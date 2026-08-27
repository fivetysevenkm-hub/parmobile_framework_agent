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

    public function mount(ThemePreferenceService $themes): void
    {
        $saved = $themes->currentForUser(Auth::user());
        $this->presetKey = $saved['preset_key'];
        $this->colors = $saved['colors'];
        $this->presetNames = collect($themes->presets())
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['name']])
            ->all();
    }

    public function applyPreset(string $presetKey, ThemePreferenceService $themes): void
    {
        $preset = $themes->presets()[$presetKey] ?? null;
        if (! $preset) {
            return;
        }

        $this->presetKey = $presetKey;
        $this->colors = $preset['colors'];
    }

    public function updatedColors(): void
    {
        $this->presetKey = 'custom';
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
                return;
            }
        }

        $themes->save($user, $this->presetKey, $this->colors);
        session()->flash('success', 'Tema berhasil disimpan untuk akun Anda.');
        $this->dispatch('theme-saved');
    }

    public function resetToDefault(ThemePreferenceService $themes): void
    {
        $this->presetKey = 'par-default';
        $this->colors = $themes->defaults();
    }

    public function render(ThemePreferenceService $themes)
    {
        return view('livewire.custom-theme', [
            'colorLabels' => [
                'bg' => 'Latar halaman',
                'panel' => 'Panel utama',
                'panel_2' => 'Panel gelap',
                'line' => 'Border dan garis',
                'text' => 'Teks utama',
                'muted' => 'Teks sekunder',
                'cyan' => 'Warna aksen utama',
                'orange' => 'Aksen peringatan',
                'red' => 'Aksen bahaya',
                'lime' => 'Aksen lime',
                'navbar_bg' => 'Latar navbar',
                'sidebar_bg' => 'Latar sidebar',
                'input_bg' => 'Latar input',
                'table_header' => 'Header tabel',
                'table_alt' => 'Baris tabel selang-seling',
                'success' => 'Status berhasil',
                'warning' => 'Status peringatan',
                'danger' => 'Teks bahaya',
                'text_sidebar' => 'Text sidebar',
                'text_header' => 'Text header / navbar',
                'text_title' => 'Text judul',
                'text_table' => 'Text isi tabel',
                'text_button' => 'Text di dalam tombol',
                'text_footer' => 'Text footer',
                'text_body' => 'Text paragraf body',
            ],
            'colorGroups' => [
                ['name' => 'Latar & Permukaan', 'keys' => ['bg', 'panel', 'panel_2', 'line', 'navbar_bg', 'sidebar_bg', 'input_bg', 'table_header', 'table_alt']],
                ['name' => 'Aksen & Status', 'keys' => ['cyan', 'orange', 'red', 'lime', 'success', 'warning', 'danger']],
                ['name' => 'Breakdown Warna Text', 'keys' => ['text_sidebar', 'text_header', 'text_title', 'text_table', 'text_button', 'text_footer', 'text_body', 'text', 'muted']],
            ],
            'presetNames' => $this->presetNames ?: collect($themes->presets())->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['name']])->all(),
            'presets' => $themes->presets(),
        ])->layout('layouts.app');
    }
}
