<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ExportExcelController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(auth()->check(), 403);

        $range = trim((string) $request->query('date_range', ''));
        $dates = $this->parseDateRange($range);

        if ($dates === null) {
            return response('Format date_range tidak valid.', 422);
        }

        [$start, $end] = $dates;
        $columns = DB::table('tbl_seq_all')
            ->select('nm_seq')
            ->selectRaw('MIN(id_seq) AS first_sequence_id')
            ->whereNotNull('nm_seq')
            ->where('nm_seq', '<>', '')
            ->groupBy('nm_seq')
            ->orderBy('first_sequence_id')
            ->pluck('nm_seq')
            ->values()
            ->all();

        if ($columns === []) {
            return response('Tidak ada data sequence.', 404);
        }

        $customers = DB::table('tbl_seq_all')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer')
            ->where('customer', '<>', '')
            ->distinct()
            ->orderBy('customer')
            ->pluck('customer')
            ->values()
            ->all();

        $rows = DB::table('tbl_seq_all')
            ->select('nm_seq')
            ->selectRaw('COALESCE(SUM(qty), 0) AS qty')
            ->selectRaw('COUNT(DISTINCT no_lot) AS lot')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('nm_seq')
            ->groupBy('nm_seq')
            ->get()
            ->keyBy('nm_seq');

        $customerRows = DB::table('tbl_seq_all')
            ->select('customer', 'nm_seq')
            ->selectRaw('COUNT(DISTINCT no_lot) AS lot')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer')
            ->where('customer', '<>', '')
            ->whereNotNull('nm_seq')
            ->groupBy('customer', 'nm_seq')
            ->get();

        $customerStats = [];
        foreach ($customerRows as $row) {
            $customerStats[$row->customer][$row->nm_seq] = (int) $row->lot;
        }

        $xml = $this->buildWorkbook($columns, $customers, $rows, $customerStats);
        $filename = 'production_dashboard_'.now('Asia/Jakarta')->format('Ymd_His').'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildWorkbook(array $columns, array $customers, object $rows, array $customerStats): string
    {
        $xml = '<?xml version="1.0"?>'.PHP_EOL;
        $xml .= '<?mso-application progid="Excel.Sheet"?>'.PHP_EOL;
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">'.PHP_EOL;
        $xml .= '<Styles><Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style><Style ss:ID="bold"><Font ss:Bold="1"/></Style></Styles>'.PHP_EOL;
        $xml .= '<Worksheet ss:Name="Production Dashboard"><Table>'.PHP_EOL;
        $xml .= '<Row ss:StyleID="header"><Cell><Data ss:Type="String">Category / Customer</Data></Cell>';
        foreach ($columns as $column) {
            $xml .= $this->cell((string) $column);
        }
        $xml .= '</Row>'.PHP_EOL;

        $xml .= '<Row>'.$this->cell('TOTAL QTY (PCS)', 'bold');
        foreach ($columns as $column) {
            $xml .= $this->cell((string) ($rows[$column]->qty ?? 0), null, 'Number');
        }
        $xml .= '</Row>'.PHP_EOL;

        $xml .= '<Row>'.$this->cell('TOTAL LOT (LOT)', 'bold');
        foreach ($columns as $column) {
            $xml .= $this->cell((string) ($rows[$column]->lot ?? 0), null, 'Number');
        }
        $xml .= '</Row>'.PHP_EOL;

        $xml .= '<Row ss:StyleID="header"><Cell ss:MergeAcross="'.count($columns).'" ><Data ss:Type="String">LOT BREAKDOWN BY CUSTOMER</Data></Cell></Row>'.PHP_EOL;
        foreach ($customers as $customer) {
            $xml .= '<Row>'.$this->cell((string) $customer);
            foreach ($columns as $column) {
                $value = (int) ($customerStats[$customer][$column] ?? 0);
                $xml .= $value > 0 ? $this->cell((string) $value, null, 'Number') : $this->cell('-');
            }
            $xml .= '</Row>'.PHP_EOL;
        }

        return $xml.'</Table></Worksheet></Workbook>'.PHP_EOL;
    }

    private function cell(string $value, ?string $style = null, string $type = 'String'): string
    {
        $styleAttribute = $style ? ' ss:StyleID="'.$style.'"' : '';
        return '<Cell'.$styleAttribute.'><Data ss:Type="'.$type.'">'.htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
    }

    private function parseDateRange(string $value): ?array
    {
        if ($value === '') {
            $now = Carbon::now('Asia/Jakarta')->seconds(0);
            $value = $now->copy()->startOfDay()->format('Y-m-d H:i').' to '.$now->format('Y-m-d H:i');
        }

        $parts = preg_split('/\s+to\s+/', $value, 2) ?: [];
        $startValue = trim($parts[0] ?? '');
        $endValue = array_key_exists(1, $parts) ? trim($parts[1]) : $startValue;

        if ($startValue === '' || $endValue === '') {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d H:i', $startValue, 'Asia/Jakarta')->startOfMinute();
            $end = Carbon::createFromFormat('Y-m-d H:i', $endValue, 'Asia/Jakarta')->endOfMinute();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfMinute(), $start->copy()->endOfMinute()];
        }

        return [$start, $end];
    }
}
