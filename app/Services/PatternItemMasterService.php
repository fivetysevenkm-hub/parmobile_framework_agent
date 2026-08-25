<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PatternItemMasterService
{
    public const ROWS_PER_PAGE = 20;

    public function items(int $page = 1, string $keyword = ''): array
    {
        $query = DB::table('tbl_pattern_item_master')
            ->select(['id_item', 'item_name', 'satuan']);
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('item_name', 'like', '%'.$keyword.'%')
                    ->orWhere('satuan', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);
        $rows = $query
            ->orderByDesc('id_item')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function item(int $id): ?array
    {
        $row = DB::table('tbl_pattern_item_master')
            ->select(['id_item', 'item_name', 'satuan'])
            ->where('id_item', $id)
            ->first();

        return $row ? (array) $row : null;
    }

    public function create(string $itemName, string $satuan): void
    {
        [$itemName, $satuan] = $this->validatedValues($itemName, $satuan);
        DB::table('tbl_pattern_item_master')->insert([
            'item_name' => $itemName,
            'satuan' => $satuan,
        ]);
    }

    public function update(int $id, string $itemName, string $satuan): void
    {
        [$itemName, $satuan] = $this->validatedValues($itemName, $satuan);
        if (! DB::table('tbl_pattern_item_master')->where('id_item', $id)->exists()) {
            throw new RuntimeException('Item yang dipilih tidak ditemukan.');
        }

        DB::table('tbl_pattern_item_master')
            ->where('id_item', $id)
            ->update([
                'item_name' => $itemName,
                'satuan' => $satuan,
            ]);
    }

    public function delete(int $id): void
    {
        if (! DB::table('tbl_pattern_item_master')->where('id_item', $id)->exists()) {
            throw new RuntimeException('Item yang dipilih tidak ditemukan.');
        }

        DB::transaction(function () use ($id): void {
            DB::table('tbl_pattern_item_master')->where('id_item', $id)->delete();
        });
    }

    private function validatedValues(string $itemName, string $satuan): array
    {
        $itemName = trim($itemName);
        $satuan = trim($satuan);
        if ($itemName === '' || mb_strlen($itemName) > 100) {
            throw new RuntimeException('Nama Item wajib diisi dan maksimal 100 karakter.');
        }
        if ($satuan === '' || mb_strlen($satuan) > 20) {
            throw new RuntimeException('Satuan wajib diisi dan maksimal 20 karakter.');
        }

        return [$itemName, $satuan];
    }
}
