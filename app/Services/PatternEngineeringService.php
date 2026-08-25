<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class PatternEngineeringService
{
    public const ROWS_PER_PAGE = 15;

    public function itemMaster(): array
    {
        return DB::table('tbl_pattern_item_master')
            ->select(['id_item', 'item_name', 'satuan'])
            ->orderBy('item_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function patterns(int $page = 1, string $keyword = ''): array
    {
        $query = DB::table('tbl_pattern')
            ->select(['id_pattern', 'pattern_code', 'pattern_name', 'image_path', 'created_at']);
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('pattern_code', 'like', '%'.$keyword.'%')
                    ->orWhere('pattern_name', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);
        $rows = $query
            ->orderByDesc('id_pattern')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function pattern(int $id): ?array
    {
        $pattern = DB::table('tbl_pattern')
            ->select(['id_pattern', 'pattern_code', 'pattern_name', 'image_path', 'created_at'])
            ->where('id_pattern', $id)
            ->first();
        if (! $pattern) {
            return null;
        }

        $steps = DB::table('tbl_pattern_step as step')
            ->leftJoin('tbl_pattern_detail as detail', 'detail.id_step', '=', 'step.id_step')
            ->leftJoin('tbl_pattern_item_master as item', 'item.id_item', '=', 'detail.id_item')
            ->select([
                'step.id_step',
                'step.step_no',
                'step.step_name',
                'detail.id_detail',
                'detail.id_item',
                'item.item_name',
                'item.satuan',
                'detail.point',
                'detail.toleransi',
                'detail.std_min',
                'detail.std_max',
                'detail.urutan',
            ])
            ->where('step.id_pattern', $id)
            ->orderBy('step.step_no')
            ->orderBy('detail.urutan')
            ->get()
            ->groupBy('id_step')
            ->map(function ($rows): array {
                $first = $rows->first();
                return [
                    'step_no' => (int) $first->step_no,
                    'step_name' => (string) ($first->step_name ?: 'STEP '.$first->step_no),
                    'items' => $rows
                        ->filter(fn ($row) => $row->id_detail !== null)
                        ->map(fn ($row) => [
                            'id_item' => (string) ($row->id_item ?? ''),
                            'point' => (string) ($row->point ?? ''),
                            'toleransi' => (string) ($row->toleransi ?? ''),
                            'std_min' => $row->std_min === null ? '' : (string) $row->std_min,
                            'std_max' => $row->std_max === null ? '' : (string) $row->std_max,
                        ])->values()->all(),
                ];
            })->values()->all();

        return [
            'header' => (array) $pattern,
            'steps' => $steps,
        ];
    }

    public function storeImage(UploadedFile $image, string $patternCode): string
    {
        $patternCode = $this->safeSegment($patternCode, 'Pattern Code');
        $root = trim((string) config('pttn.image_root'));
        if ($root === '') {
            throw new RuntimeException('Path gambar PTTN belum dikonfigurasi.');
        }

        $extension = strtolower($image->getClientOriginalExtension());
        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new RuntimeException('Gambar hanya boleh JPG, JPEG, atau PNG.');
        }

        $directory = rtrim($root, "\\/").DIRECTORY_SEPARATOR.$patternCode;
        File::ensureDirectoryExists($directory);
        $fileName = $patternCode.'_'.now()->format('YmdHis').'_'.strtolower(bin2hex(random_bytes(3))).'.'.$extension;
        $destination = $directory.DIRECTORY_SEPARATOR.$fileName;
        if (! @copy($image->getRealPath(), $destination)) {
            throw new RuntimeException('Gambar tidak dapat disalin ke folder PTTN. Periksa koneksi atau hak akses folder.');
        }

        if (mb_strlen($destination) > 255) {
            @unlink($destination);
            throw new RuntimeException('Path gambar melebihi batas 255 karakter tabel legacy.');
        }

        return $destination;
    }

    public function savePattern(?int $id, string $patternCode, string $patternName, ?string $imagePath, array $steps): int
    {
        [$patternCode, $patternName, $steps] = $this->validatePattern($patternCode, $patternName, $steps);
        if ($imagePath !== null && mb_strlen($imagePath) > 255) {
            throw new RuntimeException('Path gambar maksimal 255 karakter.');
        }

        return DB::transaction(function () use ($id, $patternCode, $patternName, $imagePath, $steps): int {
            $existing = DB::table('tbl_pattern')
                ->select(['id_pattern', 'image_path'])
                ->where('pattern_code', $patternCode)
                ->first();

            if ($existing && (! $id || (int) $existing->id_pattern !== $id)) {
                throw new RuntimeException('Pattern Code sudah digunakan oleh pattern lain.');
            }

            if ($id) {
                if (! DB::table('tbl_pattern')->where('id_pattern', $id)->exists()) {
                    throw new RuntimeException('Pattern yang dipilih tidak ditemukan.');
                }
                DB::table('tbl_pattern')->where('id_pattern', $id)->update([
                    'pattern_code' => $patternCode,
                    'pattern_name' => $patternName,
                    'image_path' => $imagePath,
                ]);
                $patternId = $id;
                DB::table('tbl_pattern_detail')->where('id_pattern', $patternId)->delete();
                DB::table('tbl_pattern_step')->where('id_pattern', $patternId)->delete();
            } else {
                $patternId = (int) DB::table('tbl_pattern')->insertGetId([
                    'pattern_code' => $patternCode,
                    'pattern_name' => $patternName,
                    'image_path' => $imagePath,
                ]);
            }

            foreach ($steps as $step) {
                $stepId = (int) DB::table('tbl_pattern_step')->insertGetId([
                    'id_pattern' => $patternId,
                    'step_no' => $step['step_no'],
                    'step_name' => 'STEP '.$step['step_no'],
                ]);

                foreach ($step['items'] as $item) {
                    DB::table('tbl_pattern_detail')->insert([
                        'id_pattern' => $patternId,
                        'id_step' => $stepId,
                        'id_item' => $item['id_item'],
                        'point' => $item['point'],
                        'toleransi' => $item['toleransi'],
                        'std_min' => $item['std_min'] === '' ? null : $item['std_min'],
                        'std_max' => $item['std_max'] === '' ? null : $item['std_max'],
                        'urutan' => $item['urutan'],
                    ]);
                }
            }

            return $patternId;
        });
    }

    public function deletePattern(int $id): void
    {
        if (! DB::table('tbl_pattern')->where('id_pattern', $id)->exists()) {
            throw new RuntimeException('Pattern yang dipilih tidak ditemukan.');
        }

        DB::transaction(function () use ($id): void {
            DB::table('tbl_pattern_detail')->where('id_pattern', $id)->delete();
            DB::table('tbl_pattern_step')->where('id_pattern', $id)->delete();
            DB::table('tbl_pattern')->where('id_pattern', $id)->delete();
        });
    }

    private function validatePattern(string $patternCode, string $patternName, array $steps): array
    {
        $patternCode = trim($patternCode);
        $patternName = trim($patternName);
        if ($patternCode === '' || mb_strlen($patternCode) > 50) {
            throw new RuntimeException('Pattern Code wajib diisi dan maksimal 50 karakter.');
        }
        if ($patternName === '' || mb_strlen($patternName) > 100) {
            throw new RuntimeException('Pattern Name wajib diisi dan maksimal 100 karakter.');
        }
        if ($steps === []) {
            throw new RuntimeException('Minimal harus ada 1 Step.');
        }

        $itemIds = DB::table('tbl_pattern_item_master')->pluck('id_item')->map(fn ($id) => (int) $id)->all();
        $validItemIds = array_flip($itemIds);
        $tolerances = ['RANGE', 'MIN', 'MAX', 'ACTUAL'];
        foreach ($steps as $stepIndex => &$step) {
            $step['step_no'] = $stepIndex + 1;
            if (($step['items'] ?? []) === []) {
                throw new RuntimeException('Setiap Step harus memiliki minimal 1 Item.');
            }
            foreach ($step['items'] as $itemIndex => &$item) {
                $item['id_item'] = (int) ($item['id_item'] ?? 0);
                $item['point'] = trim((string) ($item['point'] ?? '')) ?: $patternCode.'.'.($stepIndex + 1).'.'.($itemIndex + 1);
                $item['toleransi'] = strtoupper(trim((string) ($item['toleransi'] ?? '')));
                if (! isset($validItemIds[$item['id_item']])) {
                    throw new RuntimeException('Item belum dipilih pada Step '.($stepIndex + 1).'.');
                }
                if (! in_array($item['toleransi'], $tolerances, true)) {
                    throw new RuntimeException('Toleransi tidak valid pada Point '.$item['point'].'.');
                }
                $item['std_min'] = trim((string) ($item['std_min'] ?? ''));
                $item['std_max'] = trim((string) ($item['std_max'] ?? ''));
                if (in_array($item['toleransi'], ['MIN', 'RANGE'], true) && ($item['std_min'] === '' || ! is_numeric(str_replace(',', '.', $item['std_min'])))) {
                    throw new RuntimeException('Std Min harus diisi angka pada Point '.$item['point'].'.');
                }
                if (in_array($item['toleransi'], ['MAX', 'RANGE'], true) && ($item['std_max'] === '' || ! is_numeric(str_replace(',', '.', $item['std_max'])))) {
                    throw new RuntimeException('Std Max harus diisi angka pada Point '.$item['point'].'.');
                }
                if ($item['toleransi'] === 'MIN') {
                    $item['std_max'] = '';
                } elseif ($item['toleransi'] === 'MAX') {
                    $item['std_min'] = '';
                } elseif ($item['toleransi'] === 'ACTUAL') {
                    $item['std_min'] = '';
                    $item['std_max'] = '';
                }
                $item['std_min'] = $item['std_min'] === '' ? '' : str_replace(',', '.', $item['std_min']);
                $item['std_max'] = $item['std_max'] === '' ? '' : str_replace(',', '.', $item['std_max']);
                $item['urutan'] = $itemIndex + 1;
            }
        }
        unset($step, $item);

        return [$patternCode, $patternName, $steps];
    }

    private function safeSegment(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '' || strpbrk($value, "\\\\/:*?\"<>|") !== false) {
            throw new RuntimeException($label.' mengandung karakter path yang tidak diperbolehkan.');
        }
        return $value;
    }
}
