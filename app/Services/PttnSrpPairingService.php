<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PttnSrpPairingService
{
    public const ROWS_PER_PAGE = 25;
    public const MAPPING_ROWS_PER_PAGE = 25;

    public const ALLOWED_PROCESSES = [
        'S03', 'S04', 'S05', 'S06', 'S07', 'S08', 'S09', 'S11', 'S12', 'S13',
        'S15', 'S16', 'S17', 'S18', 'S19', 'S21', 'S22', 'S23', 'S24', 'S27',
        'S32', 'S33',
    ];

    public function srps(int $page = 1, string $searchBy = 'no_srp', string $keyword = ''): array
    {
        $searchColumn = $searchBy === 'nm_part' ? 'nm_part' : 'no_srp';
        $query = DB::table('vw_srp_complete')
            ->select(['no_srp', 'no_brg_asli', 'nm_part', 'nm_customer', 'proses']);
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where($searchColumn, 'like', '%'.$keyword.'%');
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);
        $rows = $query
            ->orderBy('no_srp')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function processes(): array
    {
        return DB::table('tblsequence')
            ->select(['id_seq', 'nm_sequence'])
            ->whereIn('id_seq', self::ALLOWED_PROCESSES)
            ->orderBy('id_seq')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function patterns(): array
    {
        return DB::table('tbl_pattern')
            ->select(['pattern_code', 'pattern_name'])
            ->orderBy('pattern_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function mappings(int $page = 1, string $keyword = ''): array
    {
        $query = DB::table('tbl_srp_pattern_mapping as mapping')
            ->leftJoin('tblsequence as sequence', 'sequence.id_seq', '=', 'mapping.process_code')
            ->select([
                'mapping.id_map',
                'mapping.no_srp',
                'mapping.process_code',
                'sequence.nm_sequence',
                'mapping.pattern_code',
                'mapping.created_at',
            ])
            ->orderByDesc('mapping.id_map');
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('mapping.no_srp', 'like', '%'.$keyword.'%')
                    ->orWhere('mapping.process_code', 'like', '%'.$keyword.'%')
                    ->orWhere('sequence.nm_sequence', 'like', '%'.$keyword.'%')
                    ->orWhere('mapping.pattern_code', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::MAPPING_ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);
        $rows = $query
            ->forPage($page, self::MAPPING_ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function saveMapping(string $noSrp, string $processCode, string $patternCode): void
    {
        $noSrp = trim($noSrp);
        $processCode = strtoupper(trim($processCode));
        $patternCode = trim($patternCode);
        if ($noSrp === '') {
            throw new RuntimeException('SRP wajib dipilih.');
        }
        if (! in_array($processCode, self::ALLOWED_PROCESSES, true)) {
            throw new RuntimeException('Process tidak termasuk daftar process yang diizinkan.');
        }
        if ($patternCode === '' || mb_strlen($patternCode) > 20) {
            throw new RuntimeException('Pattern wajib dipilih dan maksimal 20 karakter.');
        }
        if (! DB::table('vw_srp_complete')->where('no_srp', $noSrp)->exists()) {
            throw new RuntimeException('SRP tidak ditemukan pada data SRP lengkap.');
        }
        if (! DB::table('tblsequence')->where('id_seq', $processCode)->whereIn('id_seq', self::ALLOWED_PROCESSES)->exists()) {
            throw new RuntimeException('Process tidak ditemukan.');
        }
        if (! DB::table('tbl_pattern')->where('pattern_code', $patternCode)->exists()) {
            throw new RuntimeException('Pattern tidak ditemukan.');
        }

        DB::transaction(function () use ($noSrp, $processCode, $patternCode): void {
            if (DB::table('tbl_srp_pattern_mapping')
                ->where('no_srp', $noSrp)
                ->where('process_code', $processCode)
                ->exists()) {
                throw new RuntimeException('Mapping process sudah ada.');
            }
            DB::table('tbl_srp_pattern_mapping')->insert([
                'no_srp' => $noSrp,
                'process_code' => $processCode,
                'pattern_code' => $patternCode,
            ]);
        });
    }

    public function deleteMapping(int $id): void
    {
        $deleted = DB::table('tbl_srp_pattern_mapping')->where('id_map', $id)->delete();
        if ($deleted === 0) {
            throw new RuntimeException('Mapping yang dipilih tidak ditemukan.');
        }
    }
}
