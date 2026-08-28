<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_user_gemba_mode')) {
            return;
        }

        Schema::create('tbl_user_gemba_mode', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('id_user', 50)->nullable()->index();
            $table->string('mode', 20)->default('berpindah');
            $table->string('id_seq', 10)->nullable();
            $table->string('nm_sequence', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_gemba_mode');
    }
};
