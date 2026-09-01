<?php

namespace App\Services;

use App\Support\SchemaCache;
use Illuminate\Support\Facades\DB;

class LotVerifikatorService
{
    public const MAX_SEQUENCE = 25;

    public function verify(string $lotNo): array
    {
        $lotNo = trim($lotNo);
        if ($lotNo === '') {
            return $this->failure('Masukan No Lot terlebih dahulu.');
        }

        if (! SchemaCache::hasTable('tbllotdaily') || ! SchemaCache::hasTable('tblsrp') || ! SchemaCache::hasTable('tblrouting')) {
            return $this->failure('Struktur data lot/routing belum tersedia pada database.');
        }

        $lot = DB::table('tbllotdaily as lot')
            ->leftJoin('tblsrp as srp', 'lot.no_srps', '=', 'srp.no_srp')
            ->where('lot.no_lot', $lotNo)
            ->first([
                'lot.no_lot',
                'lot.no_srps as no_srp',
                'srp.id_routing',
            ]);

        if (! $lot || trim((string) ($lot->no_srp ?? '')) === '') {
            return $this->failure('No Lot tidak ditemukan di sistem.');
        }

        $routingId = trim((string) ($lot->id_routing ?? ''));
        if ($routingId === '') {
            return $this->failure('Routing untuk No Lot tersebut belum tersedia.');
        }

        $routingColumns = ['id_routing'];
        for ($index = 1; $index <= self::MAX_SEQUENCE; $index++) {
            $column = $this->sequenceColumn($index);
            if (SchemaCache::hasColumn('tblrouting', $column)) {
                $routingColumns[] = $column;
            }
        }

        $routing = DB::table('tblrouting')
            ->where('id_routing', $routingId)
            ->first($routingColumns);

        if (! $routing) {
            return $this->failure('Data routing tidak ditemukan untuk No SRP tersebut.');
        }

        $processes = $this->routingProcesses($routing);
        if ($processes === []) {
            return $this->failure('Belum ada sequence proses yang terisi pada routing ini.');
        }

        $routingCount = count($processes);
        $statusFinish = $this->globalFinishStatus($lotNo, $routingCount);
        if (strtolower(trim((string) $statusFinish)) === 'yes') {
            return [
                'ok' => true,
                'finished' => true,
                'no_lot' => $lotNo,
                'no_srp' => (string) $lot->no_srp,
                'id_routing' => $routingId,
                'routing_count' => $routingCount,
                'status_finish' => (string) $statusFinish,
                'next_process' => '',
                'processes' => [],
                'message' => 'Lot sudah selesai di semua proses Heat Treatment.',
            ];
        }

        $nextProcess = $this->resolveNextProcess($lotNo, $routing, $processes, $routingId);
        foreach ($processes as &$process) {
            $process['clickable'] = $this->sameProcess($process, $nextProcess);
        }
        unset($process);

        return [
            'ok' => true,
            'finished' => false,
            'no_lot' => $lotNo,
            'no_srp' => (string) $lot->no_srp,
            'id_routing' => $routingId,
            'routing_count' => $routingCount,
            'status_finish' => (string) $statusFinish,
            'next_process' => $nextProcess['name'] ?? '',
            'next_id_seq' => $nextProcess['id_seq'] ?? '',
            'processes' => $processes,
            'message' => 'Pilih proses aktif untuk melanjutkan proses.',
        ];
    }

    public function processForSelection(string $lotNo, int $sequenceNo): ?array
    {
        $result = $this->verify($lotNo);
        if (! ($result['ok'] ?? false)) {
            return null;
        }

        foreach ($result['processes'] as $process) {
            if ((int) ($process['sequence_no'] ?? 0) === $sequenceNo) {
                return [
                    'no_lot' => $result['no_lot'],
                    'no_srp' => $result['no_srp'],
                    'id_routing' => $result['id_routing'],
                    'process' => $process,
                    'next_process' => $result['next_process'] ?? '',
                ];
            }
        }

        return null;
    }

    private function routingProcesses(object $routing): array
    {
        $idValues = [];
        for ($index = 1; $index <= self::MAX_SEQUENCE; $index++) {
            $column = $this->sequenceColumn($index);
            $value = trim((string) ($routing->{$column} ?? ''));
            if ($value !== '') {
                $idValues[(string) $value] = $value;
            }
        }

        $names = [];
        if ($idValues !== [] && SchemaCache::hasTable('tblsequence')) {
            $names = DB::table('tblsequence')
                ->whereIn('id_seq', array_values($idValues))
                ->pluck('nm_sequence', 'id_seq')
                ->map(fn ($name): string => trim((string) $name))
                ->all();
        }

        $processes = [];
        for ($index = 1; $index <= self::MAX_SEQUENCE; $index++) {
            $column = $this->sequenceColumn($index);
            $idSeq = trim((string) ($routing->{$column} ?? ''));
            if ($idSeq === '') {
                continue;
            }

            $processes[] = [
                'sequence_no' => $index,
                'label' => 'S'.$index,
                'id_seq' => $idSeq,
                'name' => $names[(string) $idSeq] ?? $idSeq,
                'clickable' => false,
            ];
        }

        return $processes;
    }

    private function resolveNextProcess(string $lotNo, object $routing, array $processes, string $routingId): array
    {
        $first = $processes[0] ?? [];
        if (! SchemaCache::hasTable('tbl_validator')) {
            return $first;
        }

        $validator = DB::table('tbl_validator')->where('no_lot', $lotNo)->first();
        if (! $validator) {
            return $first;
        }

        $nextProcess = trim((string) $this->validatorValue($validator, 'next_process'));
        $currentSequence = trim((string) $this->validatorValue($validator, 'current_seq_code'));
        $currentProcess = trim((string) $this->validatorValue($validator, 'curr_process'));
        $finish = strtolower(trim((string) $this->validatorValue($validator, 'finish')));

        if ($nextProcess === '') {
            return $first;
        }

        if (strtoupper($nextProcess) === 'FIN') {
            $column = $this->sequenceColumnFromCode($currentSequence);
            $idSeq = $column ? trim((string) ($routing->{$column} ?? '')) : '';

            return $this->findProcess($processes, $idSeq) ?? $first;
        }

        if ($finish === 'no') {
            return $this->findProcess($processes, $currentProcess) ?? $first;
        }

        return $this->findProcess($processes, $nextProcess) ?? $first;
    }

    private function findProcess(array $processes, string $value): ?array
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        foreach ($processes as $process) {
            if ($value === strtolower((string) $process['id_seq']) || $value === strtolower((string) $process['name'])) {
                return $process;
            }
        }

        return null;
    }

    private function validatorValue(object $validator, string $field): mixed
    {
        foreach (get_object_vars($validator) as $key => $value) {
            if (strtolower((string) $key) === strtolower($field)) {
                return $value;
            }
        }

        return null;
    }

    private function sameProcess(array $process, array $target): bool
    {
        if ($target === []) {
            return false;
        }

        return strtolower((string) ($process['id_seq'] ?? '')) === strtolower((string) ($target['id_seq'] ?? ''))
            || strtolower((string) ($process['name'] ?? '')) === strtolower((string) ($target['name'] ?? ''));
    }

    private function globalFinishStatus(string $lotNo, int $routingCount): string
    {
        $routingCount = max(1, min(self::MAX_SEQUENCE, $routingCount));
        $table = 'tbl_seq_'.str_pad((string) $routingCount, 2, '0', STR_PAD_LEFT);

        if (SchemaCache::hasTable($table) && SchemaCache::hasColumn($table, 'no_lot') && SchemaCache::hasColumn($table, 'finish')) {
            return trim((string) (DB::table($table)->where('no_lot', $lotNo)->value('finish') ?? ''));
        }

        if (SchemaCache::hasTable('tbl_seq_all') && SchemaCache::hasColumn('tbl_seq_all', 'seq') && SchemaCache::hasColumn('tbl_seq_all', 'finish')) {
            return trim((string) (DB::table('tbl_seq_all')
                ->where('no_lot', $lotNo)
                ->where('seq', $routingCount)
                ->orderByDesc('id')
                ->value('finish') ?? ''));
        }

        return '';
    }

    private function sequenceColumn(int $index): string
    {
        return 'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    private function sequenceColumnFromCode(string $code): ?string
    {
        $code = trim($code);
        if (preg_match('/seq[_-]?(\d{1,2})/i', $code, $matches) === 1) {
            $index = (int) $matches[1];
        } elseif (ctype_digit($code)) {
            $index = (int) $code;
        } else {
            return null;
        }

        return $index >= 1 && $index <= self::MAX_SEQUENCE ? $this->sequenceColumn($index) : null;
    }

    private function failure(string $message): array
    {
        return [
            'ok' => false,
            'finished' => false,
            'message' => $message,
            'processes' => [],
        ];
    }
}
