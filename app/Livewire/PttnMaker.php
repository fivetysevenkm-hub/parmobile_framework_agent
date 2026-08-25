<?php

namespace App\Livewire;

use App\Services\PatternEngineeringService;
use Livewire\Component;
use Livewire\WithFileUploads;

class PttnMaker extends Component
{
    use WithFileUploads;

    public ?int $currentPatternId = null;
    public string $patternCode = '';
    public string $patternName = '';
    public string $imagePath = '';
    public $image;
    public string $imageMessage = '';
    public bool $imageCopied = false;
    public string $patternKeyword = '';
    public bool $showPatternModal = false;
    public array $patterns = [];
    public array $itemMaster = [];
    public array $steps = [];
    public int $patternPage = 1;
    public int $patternTotalPages = 1;
    public int $totalPatterns = 0;

    public function mount(PatternEngineeringService $service): void
    {
        $this->itemMaster = $service->itemMaster();
        $this->loadPatterns($service);
    }

    public function openPatternPicker(PatternEngineeringService $service): void
    {
        $this->showPatternModal = true;
        $this->patternPage = 1;
        $this->loadPatterns($service);
    }

    public function closePatternPicker(): void
    {
        $this->showPatternModal = false;
    }

    public function updatedPatternKeyword(PatternEngineeringService $service): void
    {
        $this->patternPage = 1;
        $this->loadPatterns($service);
    }

    public function nextPatternPage(PatternEngineeringService $service): void
    {
        if ($this->patternPage < $this->patternTotalPages) {
            $this->patternPage++;
            $this->loadPatterns($service);
        }
    }

    public function previousPatternPage(PatternEngineeringService $service): void
    {
        if ($this->patternPage > 1) {
            $this->patternPage--;
            $this->loadPatterns($service);
        }
    }

    public function selectPattern(int $id, PatternEngineeringService $service): void
    {
        $pattern = $service->pattern($id);
        if (! $pattern) {
            $this->addError('form', 'Pattern tidak ditemukan.');
            return;
        }

        $header = $pattern['header'];
        $this->currentPatternId = (int) $header['id_pattern'];
        $this->patternCode = (string) ($header['pattern_code'] ?? '');
        $this->patternName = (string) ($header['pattern_name'] ?? '');
        $this->imagePath = (string) ($header['image_path'] ?? '');
        $this->image = null;
        $this->imageCopied = false;
        $this->imageMessage = '';
        $this->steps = $pattern['steps'];
        $this->regeneratePoints();
        $this->showPatternModal = false;
        $this->resetValidation();
    }

    public function addStep(): void
    {
        $this->steps[] = ['step_no' => count($this->steps) + 1, 'step_name' => '', 'items' => []];
    }

    public function removeStep(int $stepIndex): void
    {
        if (! isset($this->steps[$stepIndex])) {
            return;
        }
        unset($this->steps[$stepIndex]);
        $this->steps = array_values($this->steps);
        $this->regeneratePoints();
    }

    public function duplicateStep(int $stepIndex): void
    {
        if (! isset($this->steps[$stepIndex])) {
            return;
        }
        $copy = $this->steps[$stepIndex];
        foreach ($copy['items'] as &$item) {
            $item['point'] = '';
        }
        unset($item);
        array_splice($this->steps, $stepIndex + 1, 0, [$copy]);
        $this->steps = array_values($this->steps);
        $this->regeneratePoints();
    }

    public function addItem(int $stepIndex): void
    {
        if (! isset($this->steps[$stepIndex])) {
            return;
        }
        $this->steps[$stepIndex]['items'][] = [
            'id_item' => '',
            'point' => '',
            'toleransi' => '',
            'std_min' => '',
            'std_max' => '',
        ];
        $this->regeneratePoints();
    }

    public function removeItem(int $stepIndex, int $itemIndex): void
    {
        if (! isset($this->steps[$stepIndex]['items'][$itemIndex])) {
            return;
        }
        unset($this->steps[$stepIndex]['items'][$itemIndex]);
        $this->steps[$stepIndex]['items'] = array_values($this->steps[$stepIndex]['items']);
        $this->regeneratePoints();
    }

    public function applyTolerance(int $stepIndex, int $itemIndex): void
    {
        $tolerance = strtoupper((string) ($this->steps[$stepIndex]['items'][$itemIndex]['toleransi'] ?? ''));
        if (! isset($this->steps[$stepIndex]['items'][$itemIndex])) {
            return;
        }
        if ($tolerance === 'MIN') {
            $this->steps[$stepIndex]['items'][$itemIndex]['std_max'] = '';
        } elseif ($tolerance === 'MAX') {
            $this->steps[$stepIndex]['items'][$itemIndex]['std_min'] = '';
        } elseif ($tolerance === 'ACTUAL') {
            $this->steps[$stepIndex]['items'][$itemIndex]['std_min'] = '';
            $this->steps[$stepIndex]['items'][$itemIndex]['std_max'] = '';
        }
    }

    public function updatedImage(): void
    {
        $this->imageCopied = false;
        $this->imageMessage = '';
        $this->resetValidation('image');
    }

    public function uploadPatternImage(PatternEngineeringService $service): void
    {
        $this->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']], [
            'image.required' => 'Pilih gambar terlebih dahulu.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar hanya boleh JPG, JPEG, atau PNG.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);
        if (trim($this->patternCode) === '') {
            $this->addError('patternCode', 'Isi Pattern Code sebelum upload gambar.');
            return;
        }

        try {
            $this->imagePath = $service->storeImage($this->image, $this->patternCode);
            $this->imageCopied = true;
            $this->imageMessage = 'Gambar berhasil disalin ke folder PTTN.';
            $this->resetValidation('image');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('image', $exception->getMessage());
        }
    }

    public function savePattern(PatternEngineeringService $service): void
    {
        $this->validate([
            'patternCode' => ['required', 'string', 'max:50'],
            'patternName' => ['required', 'string', 'max:100'],
        ], [
            'patternCode.required' => 'Pattern Code wajib diisi.',
            'patternName.required' => 'Pattern Name wajib diisi.',
        ]);

        try {
            if ($this->image && ! $this->imageCopied) {
                $this->imagePath = $service->storeImage($this->image, $this->patternCode);
                $this->imageCopied = true;
            }
            $id = $service->savePattern($this->currentPatternId, $this->patternCode, $this->patternName, $this->imagePath ?: null, $this->steps);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadPatterns($service);
        $this->clearForm(false);
        session()->flash('success', 'Pattern berhasil disimpan / diupdate. ID Pattern: '.$id.'.');
    }

    public function deletePattern(PatternEngineeringService $service): void
    {
        if (! $this->currentPatternId) {
            $this->addError('form', 'Pilih pattern yang akan dihapus.');
            return;
        }
        try {
            $service->deletePattern($this->currentPatternId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadPatterns($service);
        $this->clearForm(false);
        session()->flash('success', 'Pattern berhasil dihapus.');
    }

    public function clearForm(bool $resetMessages = true): void
    {
        $this->currentPatternId = null;
        $this->patternCode = '';
        $this->patternName = '';
        $this->imagePath = '';
        $this->image = null;
        $this->imageCopied = false;
        $this->imageMessage = '';
        $this->steps = [];
        if ($resetMessages) {
            $this->resetValidation();
            session()->forget('success');
        }
    }

    public function render()
    {
        return view('livewire.pttn-maker')->layout('layouts.app');
    }

    private function loadPatterns(PatternEngineeringService $service): void
    {
        $result = $service->patterns($this->patternPage, $this->patternKeyword);
        $this->patterns = $result['rows'];
        $this->totalPatterns = $result['total'];
        $this->patternTotalPages = $result['totalPages'];
        $this->patternPage = $result['page'];
    }

    private function regeneratePoints(): void
    {
        foreach ($this->steps as $stepIndex => &$step) {
            $step['step_no'] = $stepIndex + 1;
            foreach ($step['items'] as $itemIndex => &$item) {
                $item['point'] = $this->patternCode.'.'.($stepIndex + 1).'.'.($itemIndex + 1);
            }
            unset($item);
        }
        unset($step);
    }
}
