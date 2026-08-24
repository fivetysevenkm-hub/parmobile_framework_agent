<?php

namespace App\Http\Controllers;

use App\Services\ProcessProductivityReport;
use Illuminate\Http\Request;

class ProcessProductivityExportController
{
    public function __invoke(Request $request, ProcessProductivityReport $report)
    {
        $validated = $request->validate([
            'proses' => ['required', 'string', 'max:100'],
            'mesin' => ['nullable', 'string', 'max:30'],
            'tgl_awal' => ['nullable', 'date_format:Y-m-d'],
            'tgl_akhir' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $rows = $report->rowsQuery($validated['proses'], (string) ($validated['mesin'] ?? ''), $validated['tgl_awal'] ?? null, $validated['tgl_akhir'] ?? null)->get();
        $columns = ProcessProductivityReport::COLUMNS;
        $headers = ['NO LOT','PROSES','MESIN','CUSTOMER','PART NAME','QTY','PIC','START','FINISH','STD CT','DURATION','EFF%'];
        $xmlCell = static function ($value, string $type = 'String'): string {
            return '<Cell><Data ss:Type="'.$type.'">'.htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        };
        $xml = '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B7285" ss:Pattern="Solid"/></Style><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style></Styles><Worksheet ss:Name="Productivity"><Table>';
        $xml .= '<Row><Cell ss:MergeAcross="11" ss:StyleID="Title"><Data ss:Type="String">PRODUCTIVITY PER PROCESS</Data></Cell></Row><Row>';
        foreach ($headers as $header) $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        $xml .= '</Row>';
        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($columns as $column) {
                $numeric = in_array($column, ['qty','std_cycle_time','duration','achievement_pct'], true) && is_numeric($row->{$column});
                $xml .= $xmlCell($row->{$column}, $numeric ? 'Number' : 'String');
            }
            $xml .= '</Row>';
        }
        $xml .= '</Table></Worksheet></Workbook>';
        return response($xml, 200, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="productivity_per_process_'.now('Asia/Jakarta')->format('YmdHis').'.xls"', 'Cache-Control' => 'no-store, no-cache']);
    }
}
