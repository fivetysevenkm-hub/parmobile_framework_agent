<?php

namespace App\Http\Controllers;

use App\Services\RealtimeProductionReport;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RealtimeProductionCertificateController extends Controller
{
    public function __invoke(string $noLot, Request $request, RealtimeProductionReport $report)
    {
        $noLot = trim($noLot);
        abort_if($noLot === '' || mb_strlen($noLot) > 50, 404);

        $rows = $report->detailQuery($noLot)->get();
        abort_if($rows->isEmpty(), 404, 'Data posisi real-time untuk No Lot tersebut tidak ditemukan.');

        $master = (array) ($rows->first(fn ($row) => (int) ($row->seq ?? 0) === 1) ?? $rows->first());
        $printedBy = auth()->user()?->username ?? auth()->user()?->name ?? session('username') ?? 'System';
        $printedAt = now('Asia/Jakarta');
        $certificateNo = 'SRP-'.now('Asia/Jakarta')->format('Ymd-His').'-'.substr(sha1($noLot), 0, 6);

        $pttnPatternsBySequence = DB::table('tbl_lot_pttn_process')
            ->where('no_lot', $noLot)
            ->orderBy('sequence_no')
            ->orderBy('process_code')
            ->orderBy('pattern_code')
            ->get(['sequence_no', 'pattern_code'])
            ->groupBy('sequence_no')
            ->map(fn ($items) => $items->pluck('pattern_code')->filter()->unique()->implode(', '));

        $processes = $rows->values()->map(function ($row) use ($noLot, $master, $pttnPatternsBySequence): array {
            $process = (array) $row;
            $seq = (int) ($process['seq'] ?? 0);
            $status = ($process['finish'] ?? '') === 'yes'
                ? 'Finish'
                : (($process['hold'] ?? '') === 'yes' ? 'Hold' : 'On Progress');

            $qrPayload = implode("\n", [
                'PAR MOBILE - SRP PROCESS TRACEABILITY',
                'No Lot: '.($noLot ?: '-'),
                'No SRP: '.($master['no_srp'] ?? '-'),
                'Sequence: S'.$seq,
                'Process: '.($process['nm_seq'] ?? '-'),
                'Start: '.($process['start_time'] ?? '-'),
                'Finish: '.($process['finish_time'] ?? '-'),
                'Status: '.$status,
                'PTTN Pattern(s): '.($pttnPatternsBySequence->get($seq) ?: '-'),
                'Generated: '.now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);

            $process['sequence_label'] = 'S'.$seq;
            $process['status_text'] = $status;
            $process['qr_payload'] = $qrPayload;
            $process['qr_svg'] = (new Builder(
                writer: new SvgWriter(),
                data: $qrPayload,
                size: 150,
                margin: 2,
            ))->build()->getString();

            return $process;
        })->all();

        $pttnRows = DB::table('tbl_pattern_production_check as check_row')
            ->leftJoin('tbl_lot_pttn_process as lot_process', 'lot_process.id_lot_pttn_process', '=', 'check_row.id_lot_pttn_process')
            ->leftJoin('tbl_pattern as pattern', 'pattern.pattern_code', '=', 'check_row.pattern_code')
            ->select([
                'check_row.id_check',
                'check_row.pattern_code',
                'check_row.pattern_name',
                'check_row.step_no',
                'check_row.point',
                'check_row.item_name',
                'check_row.satuan',
                'check_row.toleransi',
                'check_row.std_min',
                'check_row.std_max',
                'check_row.actual_check',
                'check_row.status_check',
                'check_row.pic1',
                'check_row.datetime1',
                'check_row.pic2',
                'check_row.datetime2',
                'check_row.created_at',
                'lot_process.sequence_no',
                'lot_process.id_seq as relation_id_seq',
                'lot_process.process_code as relation_process_code',
                'lot_process.process_name as relation_process_name',
                'lot_process.routing_row_id',
                'pattern.id_pattern',
                'pattern.image_path',
            ])
            ->where('check_row.no_lot', $noLot)
            ->orderByRaw('lot_process.sequence_no IS NULL')
            ->orderBy('lot_process.sequence_no')
            ->orderBy('lot_process.process_code')
            ->orderBy('check_row.pattern_code')
            ->orderBy('check_row.step_no')
            ->orderBy('check_row.point')
            ->orderBy('check_row.id_check')
            ->get();

        $pttnGroups = $pttnRows->groupBy(fn ($row) => (($row->sequence_no ?? null) === null ? 'legacy' : 'S'.(int) $row->sequence_no).'|'.(string) ($row->pattern_code ?? ''))
            ->map(function ($group): array {
                $first = $group->first();
                $image = $this->imageDataUri($first->image_path ?? null);
                $isCheckpoint = $this->isCheckpoint($first->satuan ?? null);

                return [
                    'sequence_label' => $first->sequence_no === null ? 'Sequence belum ditautkan' : 'S'.(int) $first->sequence_no,
                    'process_code' => $first->relation_process_code ?? '-',
                    'process_name' => $first->relation_process_name ?? '-',
                    'pattern_code' => $first->pattern_code ?? '-',
                    'pattern_name' => $first->pattern_name ?? '-',
                    'image_data_uri' => $image,
                    'rows' => $group->map(function ($row): array {
                        $item = (array) $row;
                        $checkpoint = $this->isCheckpoint($row->satuan ?? null);
                        $item['is_checkpoint'] = $checkpoint;
                        $item['standard_display'] = $this->standardDisplay($row, $checkpoint);
                        $item['actual_display'] = $this->nullableDisplay($row->actual_check, $checkpoint);
                        $item['status_display'] = $this->nullableDisplay($row->status_check, $checkpoint);
                        $item['pic1_display'] = $this->nullableDisplay($row->pic1, $checkpoint);
                        $item['datetime1_display'] = $this->dateDisplay($row->datetime1, $checkpoint);
                        $item['pic2_display'] = $this->nullableDisplay($row->pic2, $checkpoint);
                        $item['datetime2_display'] = $this->dateDisplay($row->datetime2, $checkpoint);

                        return $item;
                    })->values()->all(),
                ];
            })->values()->all();

        return response()->view('print.realtime-production-certificate', [
            'certificateNo' => $certificateNo,
            'master' => $master,
            'processes' => $processes,
            'processCount' => count($processes),
            'pttnGroups' => $pttnGroups,
            'pttnCount' => $pttnRows->count(),
            'printedBy' => $printedBy,
            'printedAt' => $printedAt,
            'source' => $request->getSchemeAndHttpHost(),
        ]);
    }

    private function isCheckpoint(mixed $satuan): bool
    {
        $value = strtolower(trim((string) $satuan));

        return $value === 'checkpoint'
            || $value === 'check point'
            || str_contains($value, 'check point');
    }

    private function nullableDisplay(mixed $value, bool $checkpoint): string
    {
        if ($value !== null && trim((string) $value) !== '') {
            return (string) $value;
        }

        return $checkpoint ? '-' : 'Belum diinput';
    }

    private function dateDisplay(mixed $value, bool $checkpoint): string
    {
        if ($value === null || trim((string) $value) === '') {
            return $checkpoint ? '-' : 'Belum diinput';
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function standardDisplay(object $row, bool $checkpoint): string
    {
        if ($checkpoint) {
            return '-';
        }

        $tolerance = strtoupper(trim((string) ($row->toleransi ?? '')));
        $min = $row->std_min;
        $max = $row->std_max;

        if ($tolerance === 'RANGE' && $min !== null && $max !== null) {
            return (string) $min.' – '.(string) $max;
        }
        if ($tolerance === 'MIN' && $min !== null) {
            return '≥ '.(string) $min;
        }
        if ($tolerance === 'MAX' && $max !== null) {
            return '≤ '.(string) $max;
        }
        if ($tolerance === 'ACTUAL') {
            return 'ACTUAL';
        }

        return 'Belum diinput';
    }

    private function imageDataUri(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        $root = realpath((string) config('pttn.image_root'));
        $path = realpath($imagePath);
        if (! $root || ! $path || ! $this->isInsideRoot($path, $root) || ! File::isFile($path)) {
            return null;
        }

        $mime = File::mimeType($path);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) File::get($path));
    }

    private function isInsideRoot(string $path, string $root): bool
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);

        return $path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR);
    }
}
