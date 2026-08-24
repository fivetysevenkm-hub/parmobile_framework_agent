<?php

namespace App\Http\Controllers;

use App\Services\RankAApprovalService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class RankAImageController extends Controller
{
    public function __invoke(string $noRank, RankAApprovalService $service): Response
    {
        $nik = (string) (auth()->user()->id_user ?? session('nik') ?? '');
        $detail = $service->detailForApprover($noRank, $nik);

        abort_if($detail === null, 404, 'Data Rank A tidak ditemukan atau tidak tersedia untuk approval Anda.');

        $path = trim((string) ($detail['link_ilustrasi'] ?? ''));
        abort_if($path === '', 404, 'Ilustrasi Rank A tidak tersedia.');

        $realPath = $this->resolveImagePath($path);
        abort_if($realPath === null || @getimagesize($realPath) === false, 404, 'File ilustrasi tidak ditemukan atau bukan gambar valid.');

        return response()->file($realPath, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function resolveImagePath(string $path): ?string
    {
        $candidates = array_values(array_unique([
            $path,
            base_path($path),
            storage_path('app/'.$path),
            storage_path('app/public/'.$path),
        ]));

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath !== false && File::isFile($realPath)) {
                return $realPath;
            }
        }

        return null;
    }
}
