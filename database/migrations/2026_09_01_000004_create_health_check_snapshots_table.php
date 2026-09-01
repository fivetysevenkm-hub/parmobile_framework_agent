<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_check_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->string('year_month', 7);
            $table->string('title', 120)->nullable();
            $table->json('payload')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('lampiran_date', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_check_snapshots');
    }
};
