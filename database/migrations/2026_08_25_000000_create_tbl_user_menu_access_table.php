<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_user_menu_access')) {
            return;
        }

        Schema::create('tbl_user_menu_access', function (Blueprint $table): void {
            $table->unsignedInteger('id_user');
            $table->string('menu_key', 60);
            $table->unsignedTinyInteger('can_access')->default(0);
            $table->primary(['id_user', 'menu_key']);
            $table->index(['menu_key', 'can_access']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_menu_access');
    }
};
