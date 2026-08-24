<?php

namespace App\Http\Controllers;

use App\Services\ManpowerProductivityReport;
use Illuminate\Http\Request;

class ProductivityProcessExportController
{
    public function __invoke(Request $request, ManpowerProductivityReport $report)
    {
        $validated = $request->validate([
            'operator' => ['nullable', 'string', 'max:100'],
            'proses' => ['nullable', 'string', 'max:100'],
            'tgl_awal' => ['nullable', 'date_format:Y-m-d'],
            'tgl_akhir' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $rows = $report->rowsQuery(
            (string) ($validated['operator'] ?? ''),
            (string) ($validated['proses'] ?? ''),
            $validated['tgl_awal'] ?? null,
            $validated['tgl_akhir'] ?? null,
        )->get();

        $columns = ManpowerProductivityReport::COLUMNS;
        $headers = ['OPERATOR', 'PROSES', 'TOTAL LOT', 'TARGET CT', 'ACT CT', 'MENIT EFEKTIF', 'ACHIEVEMENT (%)'];
        $xmlCell = static function ($value, string $type = 'String'): string {
            $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            return '<Cell><Data ss:Type="'.$type.'">'.$text.'</Data></Cell>';
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B7285" ss:Pattern="Solid"/></Style><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style></Styles>';
        $xml .= '<Worksheet ss:Name="Man Power"><Table>';
        $xml .= '<Row><Cell ss:MergeAcross="6" ss:StyleID="Title"><Data ss:Type="String">MAN POWER PRODUCTIVITY</Data></Cell></Row>';
        $xml .= '<Row>';
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($columns as $column) {
                $type = in_array($column, ['total_lot', 'target_ct_val', 'actual_cycle_time', 'menit_efektif', 'achievement_pct'], true) && is_numeric($row->{$column}) ? 'Number' : 'String';
                $xml .= $xmlCell($row->{$column}, $type);
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';
        $filename = 'man_power_productivity_'.now('Asia/Jakarta')->format('YmdHis').'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
