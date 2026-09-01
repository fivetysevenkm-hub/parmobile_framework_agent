<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SchemaCache
{
    private static array $tables = [];

    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        if (! array_key_exists($table, self::$tables)) {
            self::$tables[$table] = Schema::hasTable($table);
        }

        return self::$tables[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (! self::hasTable($table)) {
            return false;
        }

        return in_array(strtolower($column), self::columns($table), true);
    }

    public static function columns(string $table): array
    {
        if (! isset(self::$columns[$table])) {
            self::$columns[$table] = self::hasTable($table)
                ? array_map('strtolower', Schema::getColumnListing($table))
                : [];
        }

        return self::$columns[$table];
    }

    public static function flush(): void
    {
        self::$tables = [];
        self::$columns = [];
    }
}
