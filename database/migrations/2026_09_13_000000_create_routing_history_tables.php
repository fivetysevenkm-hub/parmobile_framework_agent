<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_routing_history')) {
            Schema::create('tbl_routing_history', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('id_routing', 4)->index();
                $table->string('nm_routing', 100)->nullable();
                $table->unsignedTinyInteger('slot_no');
                $table->string('seq_before', 3)->nullable();
                $table->string('nama_before', 50)->nullable();
                $table->string('seq_after', 3)->nullable();
                $table->string('nama_after', 50)->nullable();
                $table->string('changed_by', 100)->nullable();
                $table->dateTime('changed_at')->index();
            });
        }

        if (! Schema::hasTable('tbl_pairing_routing_history')) {
            Schema::create('tbl_pairing_routing_history', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('no_srp', 10)->index();
                $table->string('no_part', 50)->nullable();
                $table->string('nm_part', 100)->nullable();
                $table->string('routing_before', 4)->nullable();
                $table->string('nama_before', 100)->nullable();
                $table->string('routing_after', 4)->nullable();
                $table->string('nama_after', 100)->nullable();
                $table->string('changed_by', 100)->nullable();
                $table->dateTime('changed_at')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pairing_routing_history');
        Schema::dropIfExists('tbl_routing_history');
    }
};