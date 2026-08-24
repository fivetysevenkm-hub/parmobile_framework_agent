<?php

namespace App\Http\Controllers;

use App\Services\IncomingTraceReport;
use Illuminate\Http\Request;

class IncomingTraceExportController extends Controller
{
    public function __invoke(Request $request, IncomingTraceReport $report)
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'string', 'in:'.implode(',', array_keys(IncomingTraceReport::FILTERS))],
            'tgl_awal' => ['nullable', 'date_format:Y-m-d'],
            'tgl_akhir' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $rows = $report->query(
            (string) ($validated['keyword'] ?? ''),
            (string) ($validated['filter'] ?? 'no_lot'),
            $validated['tgl_awal'] ?? null,
            $validated['tgl_akhir'] ?? null,
        )->get();

        $columns = IncomingTraceReport::COLUMNS;
        $headers = ['TGL INPUT', 'NO LOT', 'SRP', 'NO PART', 'NAMA PART', 'LOT CUST', 'SJ CUST', 'QTY', 'CUSTOMER', 'TGL DELIVERY'];
        $xmlCell = static function ($value, string $type = 'String'): string {
            $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            return '<Cell><Data ss:Type="'.$type.'">'.$text.'</Data></Cell>';
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B7285" ss:Pattern="Solid"/></Style><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style></Styles>';
        $xml .= '<Worksheet ss:Name="Incoming Trace"><Table>';
        $xml .= '<Row><Cell ss:MergeAcross="9" ss:StyleID="Title"><Data ss:Type="String">MONITORING INCOMING TRACE</Data></Cell></Row>';
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
        $filename = 'monitoring_incoming_'.now('Asia/Jakarta')->format('YmdHis').'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
