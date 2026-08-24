<?php

namespace App\Http\Controllers;

use App\Services\RealtimeProductionReport;
use Illuminate\Http\Request;

class RealtimeProductionExportController
{
    public function __invoke(Request $request, RealtimeProductionReport $report)
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'string', 'in:'.implode(',', array_keys(RealtimeProductionReport::FILTERS))],
        ]);

        $rows = $report->listQuery(
            (string) ($validated['keyword'] ?? ''),
            (string) ($validated['filter'] ?? 'no_lot'),
        )->get();
        $columns = RealtimeProductionReport::LIST_COLUMNS;
        $headers = ['NO LOT', 'SRP', 'NO PART', 'NAMA PART', 'LOT CUST', 'SJ CUST', 'CUSTOMER', 'QTY'];
        $xmlCell = static function ($value, string $type = 'String'): string {
            $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            return '<Cell><Data ss:Type="'.$type.'">'.$text.'</Data></Cell>';
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B7285" ss:Pattern="Solid"/></Style><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style></Styles>';
        $xml .= '<Worksheet ss:Name="Real Time Production"><Table>';
        $xml .= '<Row><Cell ss:MergeAcross="7" ss:StyleID="Title"><Data ss:Type="String">REAL TIME PRODUCTION</Data></Cell></Row>';
        $xml .= '<Row>';
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($columns as $column) {
                $type = $column === 'qty' && is_numeric($row->{$column}) ? 'Number' : 'String';
                $xml .= $xmlCell($row->{$column}, $type);
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';
        $filename = 'realtime_production_'.now('Asia/Jakarta')->format('YmdHis').'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
