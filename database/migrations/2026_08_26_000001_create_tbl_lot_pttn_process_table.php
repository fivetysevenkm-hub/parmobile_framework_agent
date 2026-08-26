<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_lot_pttn_process')) {
            return;
        }

        Schema::create('tbl_lot_pttn_process', function (Blueprint $table): void {
            $table->bigIncrements('id_lot_pttn_process');
            $table->string('no_lot', 50);
            $table->string('routing_row_id', 50)->nullable();
            $table->unsignedInteger('sequence_no');
            $table->string('id_seq', 50)->nullable();
            $table->string('process_code', 50);
            $table->string('process_name', 100)->nullable();
            $table->unsignedBigInteger('id_pattern')->nullable();
            $table->string('pattern_code', 50);
            $table->string('pattern_name', 100)->nullable();
            $table->timestamps();

            $table->unique(['no_lot', 'sequence_no', 'process_code', 'pattern_code'], 'lot_pttn_process_unique');
            $table->index(['no_lot', 'sequence_no'], 'lot_pttn_process_lot_sequence_idx');
            $table->index(['no_lot', 'pattern_code'], 'lot_pttn_process_lot_pattern_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_lot_pttn_process');
    }
};
