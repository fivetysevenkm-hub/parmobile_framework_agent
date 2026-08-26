<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_user_theme_preferences')) {
            return;
        }

        Schema::create('tbl_user_theme_preferences', function (Blueprint $table): void {
            $table->string('id_user', 50)->primary();
            $table->string('preset_key', 50)->default('par-default');
            $table->text('colors_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_theme_preferences');
    }
};
