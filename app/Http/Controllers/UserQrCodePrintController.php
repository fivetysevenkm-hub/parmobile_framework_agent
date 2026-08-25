<?php

namespace App\Http\Controllers;

use App\Services\UserQrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserQrCodePrintController extends Controller
{
    public function __invoke(Request $request, UserQrCodeService $service): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->take(100)
            ->all();
        abort_if($ids === [], 422, 'Tidak ada user yang dipilih.');

        $users = $service->activeUsersByIds($ids);
        abort_if($users === [], 404, 'User aktif tidak ditemukan.');

        $qrCodes = [];
        foreach ($users as $user) {
            $id = (int) $user['id_user'];
            $qrCodes[$id] = $service->qrSvg($id);
        }

        return view('print.user-qr-codes', compact('users', 'qrCodes'));
    }
}
