<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_user_menu_visibility')) {
            return;
        }

        Schema::create('tbl_user_menu_visibility', function (Blueprint $table): void {
            $table->unsignedInteger('id_user');
            $table->string('menu_key', 80);
            $table->unsignedTinyInteger('is_visible')->default(0);
            $table->primary(['id_user', 'menu_key']);
            $table->index(['menu_key', 'is_visible'], 'user_menu_visibility_key_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_menu_visibility');
    }
};
