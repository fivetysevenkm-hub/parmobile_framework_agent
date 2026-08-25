<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserQrCodeService
{
    public const ROWS_PER_PAGE = 20;

    public function activeUsers(string $keyword = '', int $page = 1): array
    {
        $query = DB::table('tbl_user as user')
            ->leftJoin('tbldept as dept', 'user.id_dept', '=', 'dept.Id_Dept')
            ->select([
                'user.id_user',
                'user.username',
                'dept.Dept as department',
            ])
            ->where('user.is_active', 1);

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('user.id_user', 'like', '%'.$keyword.'%')
                    ->orWhere('user.username', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);
        $rows = $query
            ->orderBy('user.id_user')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function activeUsersByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('tbl_user')
            ->select(['id_user', 'username'])
            ->where('is_active', 1)
            ->whereIn('id_user', $ids)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
        $order = array_flip($ids);
        usort($rows, fn (array $a, array $b) => ($order[(int) $a['id_user']] ?? PHP_INT_MAX) <=> ($order[(int) $b['id_user']] ?? PHP_INT_MAX));

        return $rows;
    }

    public function qrSvg(int $id): string
    {
        $user = DB::table('tbl_user')
            ->select('id_user')
            ->where('id_user', $id)
            ->where('is_active', 1)
            ->first();
        if (! $user) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }

        return (new Builder(
            writer: new SvgWriter(),
            data: (string) $user->id_user,
            size: 250,
            margin: 0,
        ))->build()->getString();
    }
}
