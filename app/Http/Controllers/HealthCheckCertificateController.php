<?php

namespace App\Http\Controllers;

use App\Models\HealthCheckSnapshot;
use App\Services\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthCheckCertificateController extends Controller
{
    public function __invoke(Request $request, HealthCheckService $service): View
    {
        $validated = $request->validate([
            'type' => ['required', 'in:PAR,SMS'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'lampiran_date' => ['nullable', 'date_format:Y-m-d'],
            'creator' => ['nullable', 'string', 'max:255'],
        ]);

        $type = (string) $validated['type'];
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $period = sprintf('%04d-%02d', $year, $month);
        $stored = HealthCheckSnapshot::where('type', $type)->where('year_month', $period)->first();

        if ($stored && is_array($stored->payload)) {
            $data = $stored->payload;
            $data['from_snapshot'] = true;
        } else {
            $data = $service->evaluate($type, $year, $month);
            $data['from_snapshot'] = false;
        }

        $data['type'] = $type;
        $data['year_month'] = $period;
        $data['title'] = $type === HealthCheckService::TYPE_PAR ? 'PAR Health Check' : 'SMS Health Check';
        $data['lampiran_date'] = trim((string) ($validated['lampiran_date'] ?? $data['reference_date'] ?? now('Asia/Jakarta')->format('Y-m-d')));
        $data['creator'] = trim((string) ($validated['creator'] ?? $data['created_by'] ?? (auth()->user()?->username ?: session('username', ''))));
        $data['certificate_no'] = 'HC-'.$type.'-'.$period.'-'.now('Asia/Jakarta')->format('YmdHis');

        return view('print.health-check-certificate', $data);
    }
}
