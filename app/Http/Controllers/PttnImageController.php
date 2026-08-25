<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PttnImageController extends Controller
{
    public function __invoke(Request $request, int $id): BinaryFileResponse
    {
        $pattern = DB::table('tbl_pattern')
            ->select(['id_pattern', 'image_path'])
            ->where('id_pattern', $id)
            ->first();
        abort_if(! $pattern || ! $pattern->image_path, 404);

        $root = realpath((string) config('pttn.image_root'));
        $path = realpath((string) $pattern->image_path);
        abort_if(! $root || ! $path || ! $this->isInsideRoot($path, $root), 404);
        abort_unless(File::isFile($path), 404);

        $mime = File::mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png'], true), 404);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="pttn-'.$id.'.'.($mime === 'image/png' ? 'png' : 'jpg').'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isInsideRoot(string $path, string $root): bool
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);

        return $path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR);
    }
}
