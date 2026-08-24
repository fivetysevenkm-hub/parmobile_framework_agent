<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AkakamiExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $start = Carbon::create((int) $validated['year'], (int) $validated['month'], 1, 0, 0, 0, 'Asia/Jakarta');
        $end = $start->copy()->addMonth();

        // Keep this list identical to the aliases exposed by vw_exportexcel_akakami.
        $columns = [
            'noflpp',
            'tgl_problem',
            'no_lots',
            'no_srp',
            'no_part',
            'nm_part',
            'customer',
            'no_sj_cust',
            'no_lot_cust',
            'qty_problem',
            'id_depts',
            'nm_sequence',
            'deskripsi_problem',
            'rank',
            'analisa',
            'temp_action',
            'fish_bone',
            'corrective_act',
            'penemu',
            'Dept_yg_bertanggung_jawab',
            'judgment_level',
            'rank_a',
            'pic_handler',
            'tgl_approved',
            'status_flpp',
        ];

        $rows = DB::table('vw_exportexcel_akakami')
            ->select($columns)
            ->where('tgl_problem', '>=', $start)
            ->where('tgl_problem', '<', $end)
            ->orderBy('tgl_problem')
            ->orderBy('noflpp')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B7285" ss:Pattern="Solid"/></Style><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center"/></Style></Styles>';
        $xml .= '<Worksheet ss:Name="Akakami QAQC"><Table>';
        $xml .= '<Row ss:AutoFitHeight="0" ss:Height="24"><Cell ss:MergeAcross="24" ss:StyleID="Title"><Data ss:Type="String">AKAKAMI QAQC - '.htmlspecialchars($start->format('F Y'), ENT_XML1, 'UTF-8').'</Data></Cell></Row>';

        // Use the exact view aliases so every exported column is unambiguous.
        $xml .= '<Row>';
        foreach ($columns as $column) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.htmlspecialchars($column, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
        }
        $xml .= '</Row>';

        $xmlCell = static function ($value, string $type = 'String'): string {
            $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');

            return '<Cell><Data ss:Type="'.$type.'">'.$text.'</Data></Cell>';
        };

        foreach ($rows as $row) {
            $xml .= '<Row>';

            foreach ($columns as $column) {
                $value = $row->{$column};
                $type = $column === 'qty_problem' && is_numeric($value) ? 'Number' : 'String';
                $xml .= $xmlCell($value, $type);
            }

            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';
        $filename = 'akakami_qaqc_'.$start->format('Ym').'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}

