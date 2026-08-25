<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PttnOmesService
{
    public function lotContext(string $noLot): ?array
    {
        $noLot = trim($noLot);
        if ($noLot === '') {
            return null;
        }

        $row = DB::table('tbl_seq_all as seq_all')
            ->leftJoin('tblsequence as sequence', 'sequence.id_seq', '=', 'seq_all.id_seq')
            ->select([
                'seq_all.no_lot',
                'seq_all.no_srp',
                'seq_all.no_part',
                'seq_all.nm_part',
                'seq_all.customer',
                'seq_all.id_seq',
                'seq_all.nm_seq',
                'sequence.nm_sequence',
            ])
            ->where('seq_all.no_lot', $noLot)
            ->orderBy('seq_all.id')
            ->first();

        return $row ? (array) $row : null;
    }

    public function lotPic1(string $noLot): ?string
    {
        $pic1 = DB::table('tbl_pattern_production_check')
            ->where('no_lot', trim($noLot))
            ->whereNotNull('pic1')
            ->where('pic1', '<>', '')
            ->orderBy('datetime1')
            ->orderBy('id_check')
            ->value('pic1');

        $pic1 = trim((string) $pic1);
        return $pic1 === '' ? null : $pic1;
    }

    public function mappingsForSrp(string $noSrp): array
    {
        return DB::table('tbl_srp_pattern_mapping as mapping')
            ->leftJoin('tblsequence as sequence', 'sequence.id_seq', '=', 'mapping.process_code')
            ->select([
                'mapping.id_map',
                'mapping.no_srp',
                'mapping.process_code',
                'sequence.nm_sequence',
                'mapping.pattern_code',
            ])
            ->where('mapping.no_srp', trim($noSrp))
            ->orderBy('mapping.process_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function patternHeader(string $patternCode): ?array
    {
        $row = DB::table('tbl_pattern')
            ->select(['id_pattern', 'pattern_code', 'pattern_name', 'image_path'])
            ->where('pattern_code', trim($patternCode))
            ->first();

        return $row ? (array) $row : null;
    }

    public function totalSteps(int $patternId): int
    {
        return (int) DB::table('tbl_pattern_step')
            ->where('id_pattern', $patternId)
            ->count();
    }

    public function stepRows(int $patternId, int $stepNo, string $noLot): array
    {
        return DB::table('tbl_pattern_detail as detail')
            ->join('tbl_pattern_item_master as item', 'item.id_item', '=', 'detail.id_item')
            ->join('tbl_pattern_step as step', 'step.id_step', '=', 'detail.id_step')
            ->leftJoin('tbl_pattern_production_check as check_row', function ($join) use ($noLot, $stepNo): void {
                $join->on('check_row.point', '=', 'detail.point')
                    ->where('check_row.no_lot', '=', trim($noLot))
                    ->where('check_row.step_no', '=', $stepNo);
            })
            ->select([
                'detail.point',
                'item.item_name',
                'item.satuan',
                'detail.toleransi',
                'detail.std_min',
                'detail.std_max',
                'check_row.id_check',
                'check_row.actual_check',
                'check_row.status_check',
                'check_row.pic1',
                'check_row.datetime1',
                'check_row.pic2',
                'check_row.datetime2',
            ])
            ->where('detail.id_pattern', $patternId)
            ->where('step.step_no', $stepNo)
            ->orderBy('detail.urutan')
            ->get()
            ->map(function ($row): array {
                $isCheckpoint = strtolower((string) $row->satuan) === 'checkpoint';
                return [
                    'point' => (string) ($row->point ?? ''),
                    'item_name' => (string) ($row->item_name ?? ''),
                    'satuan' => (string) ($row->satuan ?? ''),
                    'toleransi' => (string) ($row->toleransi ?? ''),
                    'std_min' => $row->std_min === null ? '' : (string) $row->std_min,
                    'std_max' => $row->std_max === null ? '' : (string) $row->std_max,
                    'actual_check' => $isCheckpoint ? '' : ($row->actual_check === null ? '' : (string) $row->actual_check),
                    'checkpoint' => $isCheckpoint && $row->datetime1 !== null,
                    'status_check' => (string) ($row->status_check ?? ''),
                    'pic1' => (string) ($row->pic1 ?? ''),
                    'datetime1' => $row->datetime1 ? (string) $row->datetime1 : '',
                    'pic2' => (string) ($row->pic2 ?? ''),
                    'double_check' => $row->datetime2 !== null,
                    'is_checkpoint' => $isCheckpoint,
                ];
            })
            ->all();
    }

    public function savePic1Row(string $noLot, array $pattern, int $stepNo, array $row, string $pic1): void
    {
        $noLot = trim($noLot);
        $pic1 = trim($pic1);
        if ($noLot === '' || mb_strlen($noLot) > 50) {
            throw new RuntimeException('No Lot HT wajib diisi dan maksimal 50 karakter.');
        }
        if (! $pattern || empty($pattern['id_pattern'])) {
            throw new RuntimeException('Pattern PTTN belum dipilih.');
        }
        if ($pic1 === '') {
            throw new RuntimeException('Sesi PIC 1 tidak ditemukan. Silakan login kembali.');
        }

        $point = trim((string) ($row['point'] ?? ''));
        if ($point === '') {
            throw new RuntimeException('Point pemeriksaan tidak valid.');
        }
        $check = $this->normaliseCheckRow($row, $point);

        DB::transaction(function () use ($noLot, $pattern, $stepNo, $row, $pic1, $point, $check): void {
            $this->persistPic1Row($noLot, $pattern, $stepNo, $row, $pic1, $point, $check['actual'], $check['status']);
        });
    }

    public function clearPic1Row(string $noLot, int $stepNo, string $point, string $pic1): void
    {
        $noLot = trim($noLot);
        $point = trim($point);
        $pic1 = trim($pic1);
        if ($noLot === '' || $point === '' || $pic1 === '') {
            throw new RuntimeException('Data autosave PIC 1 tidak lengkap.');
        }

        DB::transaction(function () use ($noLot, $stepNo, $point, $pic1): void {
            $existing = DB::table('tbl_pattern_production_check')
                ->select(['id_check', 'pic1', 'pic2'])
                ->where('no_lot', $noLot)
                ->where('step_no', $stepNo)
                ->where('point', $point)
                ->first();

            if (! $existing) {
                return;
            }
            if (! $this->sameIdentity((string) $existing->pic1, $pic1)) {
                throw new RuntimeException('Parameter ini milik PIC 1 lain. Gunakan mode Double Check.');
            }
            if (trim((string) ($existing->pic2 ?? '')) !== '') {
                throw new RuntimeException('Parameter yang sudah diverifikasi PIC 2 tidak dapat dikosongkan.');
            }

            DB::table('tbl_pattern_production_check')
                ->where('id_check', $existing->id_check)
                ->update([
                    'actual_check' => null,
                    'status_check' => null,
                    'pic1' => null,
                    'datetime1' => null,
                ]);
        });
    }

    public function setDoubleCheckRow(string $noLot, int $stepNo, string $point, string $pic2, bool $checked): void
    {
        $noLot = trim($noLot);
        $point = trim($point);
        $pic2 = trim($pic2);
        if ($noLot === '' || $point === '' || $pic2 === '') {
            throw new RuntimeException('Data Double Check tidak lengkap.');
        }

        DB::transaction(function () use ($noLot, $stepNo, $point, $pic2, $checked): void {
            $existing = DB::table('tbl_pattern_production_check')
                ->select(['id_check', 'pic1', 'pic2'])
                ->where('no_lot', $noLot)
                ->where('step_no', $stepNo)
                ->where('point', $point)
                ->first();

            if (! $existing || trim((string) ($existing->pic1 ?? '')) === '') {
                throw new RuntimeException('Parameter belum memiliki input PIC 1.');
            }
            if ($this->sameIdentity((string) $existing->pic1, $pic2)) {
                throw new RuntimeException('PIC 1 dan PIC 2 tidak boleh menggunakan akun yang sama.');
            }

            $existingPic2 = trim((string) ($existing->pic2 ?? ''));
            if ($existingPic2 !== '' && ! $this->sameIdentity($existingPic2, $pic2)) {
                throw new RuntimeException('Parameter ini sudah diverifikasi oleh PIC 2 lain.');
            }

            DB::table('tbl_pattern_production_check')
                ->where('id_check', $existing->id_check)
                ->update([
                    'pic2' => $checked ? $pic2 : null,
                    'datetime2' => $checked ? now() : null,
                ]);
        });
    }

    public function saveStep(string $noLot, array $pattern, int $stepNo, array $rows, string $pic1, ?string $doubleCheckPic = null): void
    {
        $noLot = trim($noLot);
        $pic1 = trim($pic1);
        if ($noLot === '' || mb_strlen($noLot) > 50) {
            throw new RuntimeException('No Lot HT wajib diisi dan maksimal 50 karakter.');
        }
        if (! $pattern || empty($pattern['id_pattern'])) {
            throw new RuntimeException('Pattern PTTN belum dipilih.');
        }
        if ($rows === []) {
            throw new RuntimeException('Tidak ada item pemeriksaan pada Step '.$stepNo.'.');
        }
        if ($doubleCheckPic !== null && $this->sameIdentity($pic1, trim($doubleCheckPic))) {
            throw new RuntimeException('PIC 1 dan PIC 2 tidak boleh menggunakan akun yang sama.');
        }

        DB::transaction(function () use ($noLot, $pattern, $stepNo, $rows, $pic1, $doubleCheckPic): void {
            foreach ($rows as $row) {
                $point = trim((string) ($row['point'] ?? ''));
                $isCheckpoint = ! empty($row['is_checkpoint']);
                $actual = trim((string) ($row['actual_check'] ?? ''));
                $status = strtoupper(trim((string) ($row['status_check'] ?? '')));
                $checkpoint = ! empty($row['checkpoint']);

                if ($point === '') {
                    throw new RuntimeException('Point pemeriksaan tidak valid.');
                }
                if ($isCheckpoint) {
                    $actualValue = null;
                    $status = $checkpoint ? 'OK' : null;
                } else {
                    if ($actual === '' || ! is_numeric(str_replace(',', '.', $actual))) {
                        throw new RuntimeException('Actual belum valid pada point '.$point.'.');
                    }
                    $actualNumber = (float) str_replace(',', '.', $actual);
                    $min = $row['std_min'] === '' || $row['std_min'] === null ? null : (float) str_replace(',', '.', (string) $row['std_min']);
                    $max = $row['std_max'] === '' || $row['std_max'] === null ? null : (float) str_replace(',', '.', (string) $row['std_max']);
                    $tolerance = strtoupper((string) ($row['toleransi'] ?? ''));
                    $status = match ($tolerance) {
                        'MIN' => $min !== null && $actualNumber >= $min ? 'OK' : 'NG',
                        'MAX' => $max !== null && $actualNumber <= $max ? 'OK' : 'NG',
                        'RANGE' => $min !== null && $max !== null && $actualNumber >= $min && $actualNumber <= $max ? 'OK' : 'NG',
                        'ACTUAL' => 'OK',
                        default => throw new RuntimeException('Toleransi tidak valid pada point '.$point.'.'),
                    };
                    $actualValue = $actualNumber;
                }

                $existing = DB::table('tbl_pattern_production_check')
                    ->select(['id_check', 'pic1', 'datetime1', 'pic2', 'datetime2'])
                    ->where('no_lot', $noLot)
                    ->where('step_no', $stepNo)
                    ->where('point', $point)
                    ->first();

                $picValue = $existing?->pic1 ?: $pic1;
                $datetime1 = $existing?->datetime1 ?: now();
                $values = [
                    'actual_check' => $actualValue,
                    'status_check' => $status,
                    'pic1' => $picValue,
                    'datetime1' => $datetime1,
                    'pic2' => $doubleCheckPic ?: ($existing?->pic2 ?: null),
                    'datetime2' => $doubleCheckPic ? now() : ($existing?->datetime2 ?: null),
                ];

                if ($existing) {
                    DB::table('tbl_pattern_production_check')->where('id_check', $existing->id_check)->update($values);
                } else {
                    DB::table('tbl_pattern_production_check')->insert(array_merge([
                        'no_lot' => $noLot,
                        'id_pattern' => $pattern['id_pattern'],
                        'pattern_code' => $pattern['pattern_code'],
                        'pattern_name' => $pattern['pattern_name'],
                        'step_no' => $stepNo,
                        'point' => $point,
                        'item_name' => $row['item_name'] ?? null,
                        'satuan' => $row['satuan'] ?? null,
                        'toleransi' => $row['toleransi'] ?? null,
                        'std_min' => $row['std_min'] === '' ? null : $row['std_min'],
                        'std_max' => $row['std_max'] === '' ? null : $row['std_max'],
                    ], $values));
                }
            }
        });
    }

    private function normaliseCheckRow(array $row, string $point): array
    {
        $isCheckpoint = ! empty($row['is_checkpoint']);
        $actual = trim((string) ($row['actual_check'] ?? ''));
        $checkpoint = ! empty($row['checkpoint']);

        if ($isCheckpoint) {
            return [
                'actual' => null,
                'status' => $checkpoint ? 'OK' : null,
            ];
        }
        if ($actual === '' || ! is_numeric(str_replace(',', '.', $actual))) {
            throw new RuntimeException('Actual belum valid pada point '.$point.'.');
        }

        $actualNumber = (float) str_replace(',', '.', $actual);
        $min = ($row['std_min'] ?? '') === '' || ($row['std_min'] ?? null) === null ? null : (float) str_replace(',', '.', (string) $row['std_min']);
        $max = ($row['std_max'] ?? '') === '' || ($row['std_max'] ?? null) === null ? null : (float) str_replace(',', '.', (string) $row['std_max']);
        $status = match (strtoupper((string) ($row['toleransi'] ?? ''))) {
            'MIN' => $min !== null && $actualNumber >= $min ? 'OK' : 'NG',
            'MAX' => $max !== null && $actualNumber <= $max ? 'OK' : 'NG',
            'RANGE' => $min !== null && $max !== null && $actualNumber >= $min && $actualNumber <= $max ? 'OK' : 'NG',
            'ACTUAL' => 'OK',
            default => throw new RuntimeException('Toleransi tidak valid pada point '.$point.'.'),
        };

        return ['actual' => $actualNumber, 'status' => $status];
    }

    private function persistPic1Row(string $noLot, array $pattern, int $stepNo, array $row, string $pic1, string $point, mixed $actual, ?string $status): void
    {
        $existing = DB::table('tbl_pattern_production_check')
            ->select(['id_check', 'pic1', 'datetime1', 'pic2', 'datetime2'])
            ->where('no_lot', $noLot)
            ->where('step_no', $stepNo)
            ->where('point', $point)
            ->first();

        if ($existing && trim((string) ($existing->pic1 ?? '')) !== '' && ! $this->sameIdentity((string) $existing->pic1, $pic1)) {
            throw new RuntimeException('Data ini dibuat oleh PIC 1 lain. Gunakan mode Double Check.');
        }
        if ($existing && trim((string) ($existing->pic2 ?? '')) !== '') {
            throw new RuntimeException('Parameter sudah diverifikasi PIC 2 dan tidak dapat diubah oleh PIC 1.');
        }

        $values = [
            'actual_check' => $actual,
            'status_check' => $status,
            'pic1' => $existing?->pic1 ?: $pic1,
            'datetime1' => $existing?->datetime1 ?: now(),
        ];

        if ($existing) {
            DB::table('tbl_pattern_production_check')->where('id_check', $existing->id_check)->update($values);
            return;
        }

        DB::table('tbl_pattern_production_check')->insert(array_merge([
            'no_lot' => $noLot,
            'id_pattern' => $pattern['id_pattern'],
            'pattern_code' => $pattern['pattern_code'],
            'pattern_name' => $pattern['pattern_name'],
            'step_no' => $stepNo,
            'point' => $point,
            'item_name' => $row['item_name'] ?? null,
            'satuan' => $row['satuan'] ?? null,
            'toleransi' => $row['toleransi'] ?? null,
            'std_min' => ($row['std_min'] ?? '') === '' ? null : $row['std_min'],
            'std_max' => ($row['std_max'] ?? '') === '' ? null : $row['std_max'],
        ], $values));
    }

    private function sameIdentity(string $left, string $right): bool
    {
        $left = trim($left);
        $right = trim($right);
        return $left !== '' && $right !== '' && mb_strtolower($left) === mb_strtolower($right);
    }
}
