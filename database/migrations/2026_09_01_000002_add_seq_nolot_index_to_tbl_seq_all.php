<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tbl_seq_all ADD INDEX idx_seq_nolot_order (seq, no_lot)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tbl_seq_all DROP INDEX idx_seq_nolot_order');
    }
};
