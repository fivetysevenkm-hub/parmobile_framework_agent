<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_machine_mapping')) {
            return;
        }

        Schema::create('tbl_machine_mapping', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('id_seq', 10)->index();
            $table->string('no_mesin', 30);
            $table->timestamps();

            $table->unique(['id_seq', 'no_mesin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_machine_mapping');
    }
};
