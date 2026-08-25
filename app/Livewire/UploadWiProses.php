<?php

namespace App\Livewire;

use App\Services\DocumentProcessService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadWiProses extends Component
{
    use WithFileUploads;

    public string $noSrp = '';
    public string $idRouting = '';
    public string $selectedSequence = '';
    public string $sequenceName = '';
    public string $uploaderName = '';
    public string $uploaderNik = '';
    public bool $showSrpModal = false;
    public string $srpFilterBy = 'no_srp';
    public string $srpKeyword = '';
    public int $srpPage = 1;
    public int $srpPerPage = 25;
    public array $selectedSrp = [];
    public array $sequenceOptions = [];
    public array $documents = [];
    public $upload;

    public function mount(DocumentProcessService $service): void
    {
        $this->uploaderName = (string) (session('username') ?: optional(auth()->user())->username ?: '');
        $this->uploaderNik = (string) (session('nik') ?: optional(auth()->user())->id_user ?: '');
    }

    public function openSrpSearch(): void
    {
        $this->showSrpModal = true;
        $this->srpPage = 1;
    }

    public function closeSrpSearch(): void
    {
        $this->showSrpModal = false;
    }

    public function updatedSrpFilterBy(): void
    {
        $this->srpPage = 1;
    }

    public function updatedSrpKeyword(): void
    {
        $this->srpPage = 1;
    }

    public function srpPreviousPage(): void
    {
        if ($this->srpPage > 1) {
            $this->srpPage--;
        }
    }

    public function srpNextPage(DocumentProcessService $service): void
    {
        $total = $service->srpQuery($this->srpFilterBy, $this->srpKeyword)->count();
        $lastPage = max(1, (int) ceil($total / $this->srpPerPage));
        if ($this->srpPage < $lastPage) {
            $this->srpPage++;
        }
    }

    public function chooseSrp(string $noSrp, DocumentProcessService $service): void
    {
        $this->noSrp = trim($noSrp);
        $this->loadSrp($service);
        $this->showSrpModal = false;
    }

    public function loadSrp(DocumentProcessService $service): void
    {
        $this->resetErrorBag();
        $this->noSrp = trim($this->noSrp);
        if ($this->noSrp === '') {
            $this->clearSelection();
            $this->addError('noSrp', 'No SRP belum dipilih.');
            return;
        }

        $srp = $service->srpDetail($this->noSrp);
        if (! $srp) {
            $this->clearSelection(false);
            $this->addError('noSrp', 'No SRP tidak ditemukan.');
            return;
        }

        $this->selectedSrp = $srp;
        $this->idRouting = trim((string) ($srp['id_routing'] ?? ''));
        $this->sequenceOptions = $this->idRouting !== '' ? $service->sequenceOptions($this->idRouting) : [];
        $this->selectedSequence = (string) ($this->sequenceOptions[0]['id_seq'] ?? '');
        $this->setSequenceName();
        $this->loadDocuments($service);
        if ($this->idRouting === '') {
            $this->addError('noSrp', 'Data Routing di SRP ini belum diinput. Gunakan menu Routing terlebih dahulu.');
        } elseif ($this->sequenceOptions === []) {
            $this->addError('selectedSequence', 'Routing ini belum memiliki sequence proses.');
        }
    }

    public function updatedSelectedSequence(DocumentProcessService $service): void
    {
        $this->setSequenceName();
        $this->loadDocuments($service);
    }

    public function updatedUpload(): void
    {
        $this->resetErrorBag('upload');
        $this->resetValidation('upload');
    }

    public function uploadPdf(DocumentProcessService $service): void
    {
        $this->validate([
            'noSrp' => ['required', 'string', 'max:10'],
            'selectedSequence' => ['required', 'string', 'max:3'],
            'upload' => ['required', 'file', 'max:20480'],
        ], [
            'noSrp.required' => 'No SRP belum dipilih.',
            'selectedSequence.required' => 'Nama proses belum dipilih.',
            'upload.required' => 'File PDF belum dipilih.',
            'upload.max' => 'Ukuran file PDF maksimal 20 MB.',
        ]);

        try {
            $result = $service->storePdf(
                $this->noSrp,
                $this->idRouting,
                $this->selectedSequence,
                $this->sequenceName,
                $this->upload,
                $this->uploaderNik,
            );
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('upload', $exception->getMessage());
            return;
        }

        $this->upload = null;
        $this->loadDocuments($service);
        session()->flash('success', 'Upload WI Proses berhasil: '.$result['doc_name']);
    }

    public function clearSelection(bool $clearNoSrp = true): void
    {
        if ($clearNoSrp) {
            $this->noSrp = '';
        }
        $this->selectedSrp = [];
        $this->idRouting = '';
        $this->selectedSequence = '';
        $this->sequenceName = '';
        $this->sequenceOptions = [];
        $this->documents = [];
        $this->upload = null;
        $this->resetErrorBag();
    }

    public function render(DocumentProcessService $service)
    {
        $query = $service->srpQuery($this->srpFilterBy, $this->srpKeyword);
        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $this->srpPerPage));
        $this->srpPage = min($this->srpPage, $lastPage);
        $srpRows = new LengthAwarePaginator(
            $query->forPage($this->srpPage, $this->srpPerPage)->get()->map(fn ($row) => (array) $row),
            $total,
            $this->srpPerPage,
            $this->srpPage,
            ['path' => request()->url()],
        );

        return view('livewire.upload-wi-proses', [
            'srpRows' => $srpRows,
            'srpFilters' => DocumentProcessService::SRP_FILTERS,
        ])->layout('layouts.app');
    }

    private function setSequenceName(): void
    {
        $sequence = collect($this->sequenceOptions)->first(fn (array $row) => (string) ($row['id_seq'] ?? '') === $this->selectedSequence);
        $this->sequenceName = $sequence ? (string) ($sequence['nm_sequence'] ?? '') : '';
    }

    private function loadDocuments(DocumentProcessService $service): void
    {
        $this->documents = $this->noSrp !== '' && $this->idRouting !== '' && $this->selectedSequence !== ''
            ? $service->documents($this->noSrp, $this->idRouting, $this->selectedSequence)
            : [];
    }
}
