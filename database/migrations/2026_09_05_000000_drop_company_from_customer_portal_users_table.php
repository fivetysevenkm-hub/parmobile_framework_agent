<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_customer_portal_users', function (Blueprint $table): void {
            if (Schema::hasColumn('tbl_customer_portal_users', 'company')) {
                $table->dropColumn('company');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_customer_portal_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('tbl_customer_portal_users', 'company')) {
                $table->string('company', 255)->after('password');
            }
        });
    }
};