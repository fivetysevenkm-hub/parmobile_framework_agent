<?php

namespace Tests\Feature;

use App\Livewire\HealthCheck;
use App\Models\LegacyUser;
use App\Services\HealthCheckService;
use App\Services\MenuAuthorizationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tbl_user', function (Blueprint $table): void {
            $table->string('id_user', 50)->primary();
            $table->string('username', 100)->unique();
            $table->string('pwd', 255)->default('');
            $table->integer('id_role')->default(1);
            $table->boolean('is_active')->default(true);
        });

        DB::table('tbl_user')->insert([
            ['id_user' => 'USR-DIDIK', 'username' => 'Didik', 'pwd' => 'x', 'id_role' => 1, 'is_active' => true],
            ['id_user' => 'USR-OTHER', 'username' => 'Budi', 'pwd' => 'x', 'id_role' => 1, 'is_active' => true],
        ]);
    }

    public function test_service_period_math(): void
    {
        $svc = app(HealthCheckService::class);

        $this->assertSame(['year' => 2026, 'month' => 7], $svc->previousPeriod(2026, 8));
        $this->assertSame(['year' => 2025, 'month' => 12], $svc->previousPeriod(2026, 1));
        $this->assertTrue($svc->isValidMonth(2026, 8));
        $this->assertFalse($svc->isValidMonth(2026, 13));
    }

    public function test_evaluate_on_sqlite_returns_safe_unsupported_structure(): void
    {
        $svc = app(HealthCheckService::class);
        $data = $svc->evaluate(HealthCheckService::TYPE_PAR, 2026, 8);

        $this->assertSame('PAR', $data['type']);
        $this->assertNull($data['previous']);
        $this->assertSame(0, $data['tables_scanned']);
        $this->assertSame('August 2026', $data['period_label']);
        $this->assertSame('July 2026', $data['previous_period_label']);
    }

    public function test_didik_can_access_health_check_page(): void
    {
        $didik = new LegacyUser;
        $didik->id_user = 'USR-DIDIK';
        $didik->username = 'Didik';
        $this->actingAs($didik);

        Livewire::test(HealthCheck::class)
            ->assertSee('Health Check')
            ->assertOk();
    }

    public function test_month_change_recomputes(): void
    {
        $didik = new LegacyUser;
        $didik->id_user = 'USR-DIDIK';
        $didik->username = 'Didik';
        $this->actingAs($didik);

        Livewire::test(HealthCheck::class)
            ->set('month', 9)
            ->call('recompute')
            ->assertOk();
    }

    public function test_health_check_menu_is_granted_only_to_didik(): void
    {
        $svc = app(MenuAuthorizationService::class);

        $this->assertTrue($svc->canAccess(1, 'Didik', 'health-check'));
        $this->assertFalse($svc->canAccess(2, 'Budi', 'health-check'));
        $this->assertFalse($svc->canAccess(2, 'budi', 'health-check'));
    }
}
