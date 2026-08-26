<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pattern_production_check') || Schema::hasColumn('tbl_pattern_production_check', 'id_lot_pttn_process')) {
            return;
        }

        Schema::table('tbl_pattern_production_check', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_lot_pttn_process')->nullable();
            $table->index('id_lot_pttn_process', 'pattern_check_lot_pttn_process_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_pattern_production_check') || ! Schema::hasColumn('tbl_pattern_production_check', 'id_lot_pttn_process')) {
            return;
        }

        Schema::table('tbl_pattern_production_check', function (Blueprint $table): void {
            $table->dropIndex('pattern_check_lot_pttn_process_idx');
            $table->dropColumn('id_lot_pttn_process');
        });
    }
};
