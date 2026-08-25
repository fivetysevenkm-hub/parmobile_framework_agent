<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class DocumentProcessService
{
    public const SRP_FILTERS = [
        'no_srp' => 'No SRP',
        'no_brg_asli' => 'No Part',
        'nm_part' => 'Nama Part',
        'nm_customer' => 'Customer',
        'proses' => 'Proses',
    ];

    public const SRP_COLUMNS = [
        'no_srp',
        'id_routing',
        'no_brg_asli',
        'nm_part',
        'nm_customer',
        'proses',
    ];

    private const ROUTING_SEQUENCE_COLUMNS = [
        'Seq_01', 'Seq_02', 'Seq_03', 'Seq_04', 'Seq_05', 'Seq_06', 'Seq_07',
        'Seq_08', 'Seq_09', 'Seq_10', 'Seq_11', 'Seq_12', 'Seq_13', 'Seq_14',
        'Seq_15', 'Seq_16', 'Seq_17', 'Seq_18', 'Seq_19', 'Seq_20', 'Seq_21',
        'Seq_22', 'Seq_23', 'Seq_24', 'Seq_25', 'Seq_26',
    ];

    public function srpQuery(string $filterBy = 'no_srp', string $keyword = ''): \Illuminate\Database\Query\Builder
    {
        $column = array_key_exists($filterBy, self::SRP_FILTERS) ? $filterBy : 'no_srp';
        $query = DB::table('vw_srp_complete_routing')->select(self::SRP_COLUMNS);
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where($column, 'like', '%'.$keyword.'%');
        }

        return $query->orderBy('no_srp');
    }

    public function srpOptions(): array
    {
        return DB::table('tblsrp')
            ->select('no_srp')
            ->whereNotNull('no_srp')
            ->where('no_srp', '<>', '')
            ->orderBy('no_srp')
            ->distinct()
            ->pluck('no_srp')
            ->map(fn ($noSrp) => (string) $noSrp)
            ->all();
    }

    public function srpDetail(string $noSrp): ?array
    {
        $row = DB::table('vw_srp_complete_routing')
            ->where('no_srp', trim($noSrp))
            ->select(self::SRP_COLUMNS)
            ->first();

        return $row ? (array) $row : null;
    }

    public function sequenceOptions(string $idRouting): array
    {
        $routing = DB::table('tblrouting')
            ->select(self::ROUTING_SEQUENCE_COLUMNS)
            ->where('id_routing', trim($idRouting))
            ->first();
        if (! $routing) {
            return [];
        }

        $ids = [];
        foreach (self::ROUTING_SEQUENCE_COLUMNS as $column) {
            $id = trim((string) ($routing->{$column} ?? ''));
            if ($id !== '' && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $names = DB::table('tblsequence')
            ->whereIn('id_seq', $ids)
            ->pluck('nm_sequence', 'id_seq')
            ->map(fn ($name) => (string) $name)
            ->all();

        return collect($ids)
            ->filter(fn (string $id) => array_key_exists($id, $names))
            ->map(fn (string $id) => [
                'id_seq' => $id,
                'nm_sequence' => $names[$id],
            ])
            ->values()
            ->all();
    }

    public function documents(string $noSrp, string $idRouting, string $sequence): array
    {
        return DB::table('tbl_pdf_document')
            ->select(['id', 'doc_name', 'file_path', 'upload_by', 'upload_date'])
            ->where('no_srp', trim($noSrp))
            ->where('id_routing', trim($idRouting))
            ->where('sequence', trim($sequence))
            ->orderByDesc('upload_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function storePdf(string $noSrp, string $idRouting, string $sequence, string $sequenceName, UploadedFile $file, string $uploadBy): array
    {
        $noSrp = trim($noSrp);
        $idRouting = trim($idRouting);
        $sequence = trim($sequence);
        $uploadBy = trim($uploadBy);
        if ($noSrp === '' || $idRouting === '' || $sequence === '') {
            throw new RuntimeException('SRP, routing, dan proses wajib dipilih.');
        }
        if ($uploadBy === '') {
            throw new RuntimeException('Identitas uploader tidak tersedia.');
        }
        if (! $file->isValid() || strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            throw new RuntimeException('File yang diunggah harus berupa PDF yang valid.');
        }
        if (! DB::table('tblsrp')->where('no_srp', $noSrp)->exists()) {
            throw new RuntimeException('No SRP tidak ditemukan.');
        }
        if (! DB::table('tblrouting')->where('id_routing', $idRouting)->exists()) {
            throw new RuntimeException('Routing tidak ditemukan.');
        }
        if (! collect($this->sequenceOptions($idRouting))->contains(fn (array $row) => $row['id_seq'] === $sequence)) {
            throw new RuntimeException('Proses tidak termasuk dalam routing SRP terpilih.');
        }

        $safeNoSrp = $this->safePathPart($noSrp, 'No SRP');
        $safeRouting = $this->safePathPart($idRouting, 'routing');
        $safeSequence = $this->safePathPart($sequence, 'sequence');
        $safeSequenceName = $this->safeFilePart($sequenceName ?: $sequence);
        $fileName = 'IK_'.$safeNoSrp.'_'.$safeSequenceName.'.pdf';
        $folderPath = $this->basePath().DIRECTORY_SEPARATOR.$safeNoSrp.DIRECTORY_SEPARATOR.$safeRouting.DIRECTORY_SEPARATOR.$safeSequence;
        $targetPath = $folderPath.DIRECTORY_SEPARATOR.$fileName;
        if (mb_strlen($targetPath) > 200) {
            throw new RuntimeException('Path file terlalu panjang untuk kolom file_path legacy (maksimal 200 karakter).');
        }

        if (! is_dir($folderPath) && ! mkdir($folderPath, 0775, true) && ! is_dir($folderPath)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat: '.$folderPath);
        }
        if (! copy($file->getRealPath(), $targetPath)) {
            throw new RuntimeException('File PDF gagal disalin ke folder upload.');
        }

        $id = DB::table('tbl_pdf_document')->insertGetId([
            'doc_no' => (string) Str::uuid(),
            'doc_name' => $fileName,
            'no_srp' => $noSrp,
            'id_routing' => $idRouting,
            'sequence' => $sequence,
            'file_path' => $targetPath,
            'upload_by' => $uploadBy,
            'upload_date' => now(),
        ]);

        return [
            'id' => $id,
            'doc_name' => $fileName,
            'file_path' => $targetPath,
        ];
    }

    public function document(int $id): ?array
    {
        $row = DB::table('tbl_pdf_document')->where('id', $id)->first();
        return $row ? (array) $row : null;
    }

    public function absolutePath(string $path): string
    {
        $base = rtrim($this->basePath(), "\\/");
        $candidate = $path;
        if (! $this->isAbsolutePath($candidate)) {
            $candidate = base_path($candidate);
        }

        $normalizedBase = $this->normalizePath($base);
        $normalizedCandidate = $this->normalizePath($candidate);
        if ($normalizedCandidate !== $normalizedBase && ! str_starts_with($normalizedCandidate, $normalizedBase.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Path dokumen berada di luar folder upload yang dikonfigurasi.');
        }

        return $candidate;
    }

    private function basePath(): string
    {
        $path = trim((string) config('documents.wi_process_upload_path'));
        if ($path === '') {
            throw new RuntimeException('WI_PROCESS_UPLOAD_PATH belum dikonfigurasi di .env.');
        }
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    private function safePathPart(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '' || $value === '.' || $value === '..' || strpbrk($value, '\\\/:*?"<>|') !== false) {
            throw new RuntimeException($label.' memiliki karakter path yang tidak valid.');
        }
        return $value;
    }

    private function safeFilePart(string $value): string
    {
        $value = trim(preg_replace('/[^\pL\pN _.-]+/u', '_', $value) ?? '');
        $value = trim($value, '. ');
        return $value !== '' ? mb_substr($value, 0, 140) : 'PROCESS';
    }

    private function isAbsolutePath(string $path): bool
    {
        return (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'))
            || str_starts_with($path, '\\\\')
            || str_starts_with($path, '/');
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), "\\/");
    }
}
