<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailCertificateController extends Controller
{
    private const EVENT_LABELS = [
        'login' => 'Login sukses',
        'login_failed' => 'Login gagal',
        'logout' => 'Logout',
        'password_change' => 'Ganti password',
        'access' => 'Akses menu',
        'access_denied' => 'Akses menu ditolak',
        'create' => 'Data dibuat',
        'update' => 'Data diubah',
        'delete' => 'Data dihapus',
    ];

    private const EVENT_GROUPS = [
        'authentication' => ['login', 'login_failed', 'logout', 'password_change'],
        'access' => ['access', 'access_denied'],
        'data_change' => ['create', 'update', 'delete'],
    ];

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:50'],
            'module' => ['nullable', 'string', 'max:191'],
            'username' => ['nullable', 'string', 'max:191'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $filters = [
            'search' => trim((string) ($validated['search'] ?? '')),
            'event' => trim((string) ($validated['event'] ?? '')),
            'module' => trim((string) ($validated['module'] ?? '')),
            'username' => trim((string) ($validated['username'] ?? '')),
            'dateFrom' => $validated['date_from'] ?? null,
            'dateTo' => $validated['date_to'] ?? null,
        ];

        abort_if(
            $filters['search'] === '' && $filters['event'] === '' && $filters['module'] === '' && $filters['username'] === '' && ! $filters['dateFrom'] && ! $filters['dateTo'],
            422,
            'Pilih minimal satu filter Audit Trail sebelum mencetak sertifikat.',
        );
        abort_if(
            ($filters['dateFrom'] && ! $filters['dateTo']) || (! $filters['dateFrom'] && $filters['dateTo']),
            422,
            'Tanggal awal dan akhir harus dipilih bersama.',
        );

        $query = $this->buildQuery($filters);
        $total = (clone $query)->count();
        $rows = $query->orderBy('id', 'desc')->limit(500)->get();

        $summary = $this->summarize($rows, $total);

        $loggedInUser = auth()->user();

        return view('print.audit-certificate', [
            'certificateNo' => 'AUD-'.now('Asia/Jakarta')->format('Ymd-His'),
            'filters' => $filters,
            'total' => $total,
            'rows' => $rows,
            'summary' => $summary,
            'eventLabels' => self::EVENT_LABELS,
            'printedBy' => $loggedInUser?->username ?? $loggedInUser?->name ?? session('username') ?? 'System',
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }

    private function buildQuery(array $filters)
    {
        $query = AuditTrail::query();

        if ($filters['event'] !== '') {
            $query->where('event', $filters['event']);
        }
        if ($filters['module'] !== '') {
            $query->where('module', $filters['module']);
        }
        if ($filters['username'] !== '') {
            $value = $filters['username'];
            $query->where(function ($q) use ($value): void {
                $q->where('username', 'like', '%'.$value.'%')
                    ->orWhere('user_id', 'like', '%'.$value.'%');
            });
        }
        if ($filters['dateFrom']) {
            $query->where('created_at', '>=', $filters['dateFrom'].' 00:00:00');
        }
        if ($filters['dateTo']) {
            $query->where('created_at', '<=', $filters['dateTo'].' 23:59:59');
        }
        if ($filters['search'] !== '') {
            $value = $filters['search'];
            $query->where(function ($q) use ($value): void {
                $q->where('action', 'like', '%'.$value.'%')
                    ->orWhere('url', 'like', '%'.$value.'%')
                    ->orWhere('module', 'like', '%'.$value.'%');
            });
        }

        return $query;
    }

    private function summarize($rows, int $total): array
    {
        $count = static fn (string $event): int => $rows->where('event', $event)->count();

        $groupCounts = [];
        foreach (self::EVENT_GROUPS as $group => $events) {
            $groupCounts[$group] = $rows->whereIn('event', $events)->count();
        }

        return [
            'total' => $total,
            'shown' => $rows->count(),
            'login' => $count('login'),
            'login_failed' => $count('login_failed'),
            'logout' => $count('logout'),
            'password_change' => $count('password_change'),
            'access' => $count('access'),
            'access_denied' => $count('access_denied'),
            'create' => $count('create'),
            'update' => $count('update'),
            'delete' => $count('delete'),
            'auth' => $groupCounts['authentication'],
            'menu' => $groupCounts['access'],
            'data' => $groupCounts['data_change'],
        ];
    }
}
