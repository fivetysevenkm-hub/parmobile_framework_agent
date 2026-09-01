<?php

namespace App\Services;

use App\Models\HealthCheckSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    public const TYPE_PAR = 'PAR';

    public const TYPE_SMS = 'SMS';

    private const EXCLUDED_TABLES = ['tblsrp', 'tbllotdaily'];

    private const AUDIT_EVENTS = [
        'login' => 'login',
        'login_failed' => 'login_failed',
        'logout' => 'logout',
        'password_change' => 'password_change',
        'access' => 'access',
        'access_denied' => 'access_denied',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
    ];

    public function isValidMonth(int $year, int $month): bool
    {
        return $year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12;
    }

    /**
     * Compute the full health-check snapshot for a given reference month.
     *
     * @return array<string, mixed>
     */
    public function evaluate(string $type, int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $range = $this->monthRange($year, $month);
        $prev = $this->previousPeriod($year, $month);

        if (! $this->supportsIntrospection()) {
            return [
                'type' => $type,
                'year_month' => $period,
                'period_label' => $this->periodLabel($year, $month),
                'previous_period_label' => $this->periodLabel((int) $prev['year'], (int) $prev['month']),
                'checked_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'reference_date' => now('Asia/Jakarta')->format('Y-m-d'),
                'tables_scanned' => 0,
                'table_names' => [],
                'table_stats' => [],
                'record_total' => 0,
                'bytes_total' => 0,
                'new_users' => 0,
                'audit' => array_fill_keys(array_values(self::AUDIT_EVENTS), 0),
                'audit_total' => 0,
                'db' => ['ok' => false, 'checks' => [], 'version' => 'Unavailable', 'score' => 0, 'total' => 0],
                'syntax' => ['ok' => false, 'valid' => 0, 'total' => 0, 'score' => 'UNAVAILABLE'],
                'previous' => null,
            ];
        }

        $data = $this->snapshot($type);
        $prevSnapshot = $this->previousSnapshot($type, $period);

        $data['type'] = $type;
        $data['year_month'] = $period;
        $data['period_label'] = $this->periodLabel($year, $month);
        $data['previous_period_label'] = $this->periodLabel((int) $prev['year'], (int) $prev['month']);
        $data['checked_at'] = now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $data['reference_date'] = now('Asia/Jakarta')->format('Y-m-d');

        $data['new_users'] = $this->newUsersBetween($range['start'], $range['end']);
        $data['audit'] = $this->auditRecap($range['start'], $range['end']);
        $data['audit_total'] = (int) array_sum($data['audit']);

        // NB: "previous" values are taken from the last stored snapshot if any,
        // otherwise fall back to the same point-in-time values.
        $data['previous'] = $prevSnapshot ? $this->previousFromStored($prevSnapshot) : $data;

        return $data;
    }

    /**
     * @return array{year:int, month:int}
     */
    public function previousPeriod(int $year, int $month): array
    {
        if ($month === 1) {
            return ['year' => $year - 1, 'month' => 12];
        }

        return ['year' => $year, 'month' => $month - 1];
    }

    public function periodLabel(int $year, int $month): string
    {
        return Carbon::create($year, $month, 1)->format('F Y');
    }

    /**
     * Store/update the snapshot for the given type + period and return it as array.
     *
     * @return array<string, mixed>
     */
    public function store(string $type, int $year, int $month, array $extra): array
    {
        $data = $this->evaluate($type, $year, $month);

        $payload = array_merge($data, $extra);
        $record = HealthCheckSnapshot::updateOrCreate(
            ['type' => $type, 'year_month' => $data['year_month']],
            [
                'title' => trim((string) ($extra['title'] ?? '')),
                'payload' => $payload,
                'created_by' => trim((string) ($extra['created_by'] ?? '')),
                'lampiran_date' => trim((string) ($extra['lampiran_date'] ?? '')),
                'created_at' => now('Asia/Jakarta'),
            ],
        );

        $record['payload'] = $payload;

        return (array) $record->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function fromArray(array $payload): array
    {
        return $payload;
    }

    private function supportsIntrospection(): bool
    {
        try {
            $driver = DB::connection()->getDriverName();

            return in_array($driver, ['mysql', 'mariadb'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Point-in-time snapshot of the database for the requested scope.
     *
     * @return array<string, mixed>
     */
    private function snapshot(string $type): array
    {
        $tables = $this->scopedTables($type);

        $tableStats = [];
        $recordTotal = 0;
        $bytesTotal = 0;
        foreach ($tables as $table) {
            $info = $this->tableStats($table);
            if (! $info) {
                continue;
            }
            $tableStats[$table] = $info;
            $recordTotal += $info['records'];
            $bytesTotal += $info['bytes'];
        }

        return [
            'tables_scanned' => count($tableStats),
            'table_names' => array_keys($tableStats),
            'table_stats' => $tableStats,
            'record_total' => $recordTotal,
            'bytes_total' => $bytesTotal,
            'db' => $this->databaseCondition(),
            'syntax' => $this->syntaxCondition(),
        ];
    }

    /**
     * Which tables belong to the given health-check scope.
     *
     * @return list<string>
     */
    private function scopedTables(string $type): array
    {
        $all = DB::select(
            "SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
        );
        $names = array_map(static fn ($row): string => (string) $row->t, $all);

        if ($type === self::TYPE_SMS) {
            return array_values(array_intersect(self::EXCLUDED_TABLES, $names));
        }

        return array_values(array_diff($names, self::EXCLUDED_TABLES));
    }

    /**
     * @return array{exists: bool, records: int, bytes: int}
     */
    private function tableStats(string $table): array
    {
        $tables = DB::select(
            'SELECT TABLE_ROWS AS row_count, DATA_LENGTH + INDEX_LENGTH AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );
        $info = $tables[0] ?? null;
        if (! $info) {
            return ['exists' => false, 'records' => 0, 'bytes' => 0];
        }

        $records = $this->countRows($table);

        return [
            'exists' => true,
            'records' => $records,
            'bytes' => (int) ((int) $info->bytes),
        ];
    }

    private function countRows(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCondition(): array
    {
        $checks = [];

        $status = DB::select("SHOW GLOBAL STATUS WHERE Variable_name = 'Threads_connected'");
        $checks['online'] = [$status !== [], 'Database reachable'];

        try {
            $engine = DB::selectOne('SELECT @@default_storage_engine AS e');
            $checks['engine'] = [! empty($engine) && mb_strtolower((string) $engine->e) === 'innodb', 'Storage engine InnoDB'];
        } catch (\Throwable $e) {
            $checks['engine'] = [false, 'Storage engine InnoDB'];
        }

        try {
            $mode = DB::selectOne('SELECT @@sql_mode AS m');
            $checks['strict'] = [str_contains(strtoupper((string) ($mode->m ?? '')), 'STRICT'), 'STRICT_TRANS_TABLES enabled'];
        } catch (\Throwable $e) {
            $checks['strict'] = [false, 'STRICT_TRANS_TABLES enabled'];
        }

        $version = DB::selectOne('SELECT VERSION() AS v');

        return [
            'ok' => collect($checks)->every(fn ($item) => (bool) $item[0]),
            'checks' => $this->checksToArray($checks),
            'version' => (string) ($version->v ?? 'Unknown'),
            'engine' => (string) (DB::selectOne('SELECT @@default_storage_engine AS e')->e ?? ''),
            'score' => (int) collect($checks)->reduce(fn (int $carry, $item) => $carry + ((bool) $item[0] ? 1 : 0), 0),
            'total' => count($checks),
        ];
    }

    private function syntaxCondition(): array
    {
        $dirs = [
            base_path('app'),
            base_path('routes'),
        ];
        $files = [];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        $valid = 0;
        foreach ($files as $path) {
            if ($this->phpHasSyntaxError($path)) {
                continue;
            }
            $valid++;
        }

        $ok = $valid === count($files) && count($files) > 0;

        return [
            'ok' => $ok,
            'valid' => $valid,
            'total' => count($files),
            'score' => $ok ? 'OK' : 'NEEDS_ATTENTION',
        ];
    }

    private function phpHasSyntaxError(string $path): bool
    {
        $code = @file_get_contents($path);
        if ($code === false) {
            return true;
        }

        try {
            token_get_all($code, TOKEN_PARSE);

            return false;
        } catch (\ParseError $e) {
            return true;
        }
    }

    private function newUsersBetween(string $start, string $end): int
    {
        try {
            return DB::table('tbl_user')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<string, int>
     */
    private function auditRecap(string $start, string $end): array
    {
        try {
            $rows = DB::table('tbl_audit_trail')
                ->selectRaw('event, COUNT(*) AS total')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->groupBy('event')
                ->pluck('total', 'event');
        } catch (\Throwable $e) {
            return array_fill_keys(array_values(self::AUDIT_EVENTS), 0);
        }

        $recap = array_fill_keys(array_values(self::AUDIT_EVENTS), 0);
        foreach ($rows as $event => $count) {
            $event = (string) $event;
            if (array_key_exists($event, $recap)) {
                $recap[$event] = (int) $count;
            }
        }

        return $recap;
    }

    /**
     * @return array{start: string, end: string}
     */
    private function monthRange(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $next = $month === 12
            ? sprintf('%04d-01-01 00:00:00', $year + 1)
            : sprintf('%04d-%02d-01 00:00:00', $year, $month + 1);

        return ['start' => $start, 'end' => $next];
    }

    private function previousSnapshot(string $type, string $period): ?array
    {
        try {
            $record = HealthCheckSnapshot::where('type', $type)
                ->where('year_month', '<', $period)
                ->orderByDesc('year_month')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }

        return $record && is_array($record->payload) ? $record->payload : null;
    }

    private function previousFromStored(array $payload): array
    {
        $data = [];
        foreach (['record_total', 'bytes_total', 'tables_scanned', 'new_users', 'audit_total'] as $key) {
            $data[$key] = (int) ($payload[$key] ?? 0);
        }
        $data['audit'] = (array) ($payload['audit'] ?? []);
        $data['db'] = (array) ($payload['db'] ?? []);
        $data['syntax'] = (array) ($payload['syntax'] ?? []);
        $data['period_label'] = (string) ($payload['period_label'] ?? '');

        return $data;
    }

    private function checksToArray(array $checks): array
    {
        $out = [];
        foreach ($checks as $name => $item) {
            $out[] = [
                'name' => (string) $name,
                'ok' => (bool) $item[0],
                'label' => (string) $item[1],
            ];
        }

        return $out;
    }
}
