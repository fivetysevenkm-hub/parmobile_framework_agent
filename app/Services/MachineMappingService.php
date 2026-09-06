<?php

namespace App\Services;

use App\Support\SchemaCache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MachineMappingService
{
    public function sequences(): array
    {
        return DB::table('tblsequence')
            ->select(['id_seq', 'nm_sequence'])
            ->orderBy('id_seq')
            ->get()
            ->map(fn ($row): array => [
                'id_seq' => (string) ($row->id_seq ?? ''),
                'nm_sequence' => (string) ($row->nm_sequence ?? ''),
            ])
            ->all();
    }

    public function machines(): array
    {
        if (! SchemaCache::hasTable('tblmesin')) {
            return [];
        }

        return DB::table('tblmesin')
            ->orderBy('mesin')
            ->get(['no_mesin', 'mesin'])
            ->map(fn ($row): array => [
                'no_mesin' => (string) ($row->no_mesin ?? ''),
                'mesin' => (string) ($row->mesin ?? ''),
            ])
            ->all();
    }

    public function mappedMachines(string $idSeq): array
    {
        if (! SchemaCache::hasTable('tbl_machine_mapping')) {
            return [];
        }

        return DB::table('tbl_machine_mapping')
            ->where('id_seq', trim($idSeq))
            ->orderBy('no_mesin')
            ->pluck('no_mesin')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    public function hasMapping(string $idSeq): bool
    {
        if (! SchemaCache::hasTable('tbl_machine_mapping')) {
            return false;
        }

        return DB::table('tbl_machine_mapping')->where('id_seq', trim($idSeq))->exists();
    }

    public function mappedCounts(): array
    {
        if (! SchemaCache::hasTable('tbl_machine_mapping')) {
            return [];
        }

        return DB::table('tbl_machine_mapping')
            ->groupBy('id_seq')
            ->pluck(DB::raw('COUNT(*)'), 'id_seq')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    public function mappingOptions(string $idSeq): array
    {
        $mapped = array_fill_keys($this->mappedMachines($idSeq), true);

        return array_map(
            fn (array $machine): array => [
                'no_mesin' => $machine['no_mesin'],
                'mesin' => $machine['mesin'],
                'mapped' => isset($mapped[$machine['no_mesin']]),
            ],
            $this->machines(),
        );
    }

    public function save(string $idSeq, array $noMesin): void
    {
        $idSeq = trim($idSeq);
        if ($idSeq === '') {
            throw new RuntimeException('Sequence wajib dipilih.');
        }

        $allowed = DB::table('tblsequence')->where('id_seq', $idSeq)->exists();
        if (! $allowed) {
            throw new RuntimeException('Sequence tidak terdaftar pada master sequence.');
        }

        $noMesin = collect($noMesin)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($idSeq, $noMesin): void {
            DB::table('tbl_machine_mapping')->where('id_seq', $idSeq)->delete();

            if ($noMesin !== []) {
                DB::table('tbl_machine_mapping')->insert(array_map(
                    fn (string $machine): array => [
                        'id_seq' => $idSeq,
                        'no_mesin' => $machine,
                    ],
                    $noMesin,
                ));
            }
        });
    }
}
