<?php

namespace App\Http\Controllers;

use App\Services\DocumentProcessService;
use Illuminate\Http\Response;

class DocumentProcessPdfController extends Controller
{
    public function __invoke(int $id, DocumentProcessService $service): Response
    {
        $document = $service->document($id);
        abort_unless($document, 404);

        $path = $service->absolutePath((string) ($document['file_path'] ?? ''));
        abort_unless(is_file($path) && is_readable($path), 404, 'File dokumen tidak ditemukan.');

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', (string) ($document['doc_name'] ?? 'document.pdf')).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
