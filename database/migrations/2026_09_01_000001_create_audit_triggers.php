<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SENSITIVE_COLUMNS = [
        'pwd',
        'password',
        'passwd',
        'remember_token',
        'secret',
        'api_token',
    ];

    private const TRIGGER_TABLES = [
        'tblabnreport',
        'tblakakami',
        'tblranka',
        'tbl_approval_abnreport',
        'tbl_approval_akakami',
        'tbl_approval_ranka',
        'tbl_delete_nonconform',
        'tbl_lot_pttn_process',
        'tbl_pattern',
        'tbl_pattern_detail',
        'tbl_pattern_item_master',
        'tbl_pattern_production_check',
        'tbl_pattern_step',
        'tbl_pdf_document',
        'tbl_srp_pattern_mapping',
    ];

    public function up(): void
    {
        foreach (self::TRIGGER_TABLES as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            foreach (['insert', 'update', 'delete'] as $event) {
                $this->createTrigger($table, $event);
            }
        }
    }

    public function down(): void
    {
        foreach (self::TRIGGER_TABLES as $table) {
            DB::statement("DROP TRIGGER IF EXISTS `par_audit_{$table}_insert`");
            DB::statement("DROP TRIGGER IF EXISTS `par_audit_{$table}_update`");
            DB::statement("DROP TRIGGER IF EXISTS `par_audit_{$table}_delete`");
        }
    }

    private function tableExists(string $table): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        ) !== null;
    }

    private function createTrigger(string $table, string $event): void
    {
        $columns = $this->columnsOf($table);
        $pkColumns = $this->primaryKeyOf($table);

        $triggerName = "par_audit_{$table}_{$event}";
        $suffix = $event === 'insert' ? 'NEW' : 'OLD';

        $eventLabel = match ($event) {
            'insert' => 'create',
            'update' => 'update',
            default => 'delete',
        };
        $action = match ($event) {
            'insert' => "Data baru di tabel {$table}",
            'update' => "Data diperbarui di tabel {$table}",
            default => "Data dihapus dari tabel {$table}",
        };
        $actionSql = $this->quote($action);

        $oldJson = $this->buildJson('OLD', $columns);
        $newJson = $this->buildJson('NEW', $columns);

        $pkSql = 'NULL';
        if ($pkColumns !== []) {
            $pkObjParts = [];
            foreach ($pkColumns as $pk) {
                $pkObjParts[] = $this->quote($pk).', '."{$suffix}.`{$pk}`";
            }
            $pkSql = 'JSON_OBJECT('.implode(', ', $pkObjParts).')';
        }

        $oldDataSql = $event === 'insert' ? 'NULL' : $oldJson;
        $newDataSql = $event === 'delete' ? 'NULL' : $newJson;

        $statement = <<<SQL
        CREATE TRIGGER `{$triggerName}` AFTER {$event} ON `{$table}` FOR EACH ROW
        BEGIN
            INSERT INTO tbl_audit_trail (user_id, username, event, module, action, method, old_data, new_data, metadata, created_at)
            VALUES (
                @audit_user_id,
                @audit_user,
                '{$eventLabel}',
                '{$table}',
                {$actionSql},
                '{$event}',
                {$oldDataSql},
                {$newDataSql},
                {$pkSql},
                NOW()
            );
        END
        SQL;

        DB::statement($statement);
    }

    private function buildJson(string $prefix, array $columns): string
    {
        $parts = [];
        foreach ($columns as $column) {
            if (in_array(mb_strtolower($column), self::SENSITIVE_COLUMNS, true)) {
                $parts[] = $this->quote($column).", '***'";

                continue;
            }
            $parts[] = $this->quote($column).', '."{$prefix}.`{$column}`";
        }

        return 'JSON_OBJECT('.implode(', ', $parts).')';
    }

    private function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function columnsOf(string $table): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table],
        );

        return array_map(fn (object $row): string => (string) $row->COLUMN_NAME, $rows);
    }

    private function primaryKeyOf(string $table): array
    {
        $rows = DB::select(
            "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION",
            [$table],
        );

        return array_map(fn (object $row): string => (string) $row->COLUMN_NAME, $rows);
    }
};
