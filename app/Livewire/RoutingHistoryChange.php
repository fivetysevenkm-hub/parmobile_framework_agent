<?php

namespace App\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class RoutingHistoryChange extends Component
{
    use WithPagination;

    public string $activeTab = 'routing';

    public string $search = '';

    public int $perPage = 15;

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function refreshGrid(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $table = $this->activeTab === 'pairing' ? 'tbl_pairing_routing_history' : 'tbl_routing_history';

        if (! Schema::hasTable($table)) {
            $rows = new LengthAwarePaginator([], 0, $this->perPage, LengthAwarePaginator::resolveCurrentPage());

            return view('livewire.routing-history-change', [
                'rows' => $rows,
                'totalRows' => 0,
            ])->layout('layouts.app');
        }

        $rows = $this->activeTab === 'pairing'
            ? $this->pairingQuery()->paginate($this->perPage)->through(fn ($row) => (array) $row)
            : $this->routingQuery()->paginate($this->perPage)->through(fn ($row) => (array) $row);

        return view('livewire.routing-history-change', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
        ])->layout('layouts.app');
    }

    private function routingQuery()
    {
        return DB::table('tbl_routing_history')
            ->select([
                'id', 'id_routing', 'nm_routing', 'slot_no',
                'seq_before', 'nama_before', 'seq_after', 'nama_after',
                'changed_by', 'changed_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('id_routing', 'like', $term)
                        ->orWhere('nm_routing', 'like', $term)
                        ->orWhere('seq_before', 'like', $term)
                        ->orWhere('seq_after', 'like', $term)
                        ->orWhere('nama_before', 'like', $term)
                        ->orWhere('nama_after', 'like', $term)
                        ->orWhere('changed_by', 'like', $term);
                });
            })
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }

    private function pairingQuery()
    {
        return DB::table('tbl_pairing_routing_history')
            ->select([
                'id', 'no_srp', 'no_part', 'nm_part',
                'routing_before', 'nama_before', 'routing_after', 'nama_after',
                'changed_by', 'changed_at',
            ])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('no_srp', 'like', $term)
                        ->orWhere('no_part', 'like', $term)
                        ->orWhere('nm_part', 'like', $term)
                        ->orWhere('routing_before', 'like', $term)
                        ->orWhere('routing_after', 'like', $term)
                        ->orWhere('changed_by', 'like', $term);
                });
            })
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }
}