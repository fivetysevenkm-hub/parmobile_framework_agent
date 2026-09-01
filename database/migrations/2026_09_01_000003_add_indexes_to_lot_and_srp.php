<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('tbllotdaily', 'no_srps', 'idx_lotdaily_nosrps');
        $this->addIndexIfMissing('tblsrp', 'no_srp', 'idx_srp_nosrp');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('tbllotdaily', 'idx_lotdaily_nosrps');
        $this->dropIndexIfExists('tblsrp', 'idx_srp_nosrp');
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index]
        ) !== null;
    }

    private function addIndexIfMissing(string $table, string $column, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }
};
