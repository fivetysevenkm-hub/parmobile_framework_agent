<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditTrailCertificateController;
use App\Livewire\AuditTrailLog;
use App\Models\AuditTrail;
use App\Models\LegacyUser;
use App\Services\AuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class AuditTrailTest extends TestCase
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

        Schema::create('tbl_audit_trail', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('event', 30);
            $table->string('module', 80)->nullable();
            $table->string('action', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('old_data')->nullable();
            $table->text('new_data')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function test_logger_writes_a_record_and_casts_json(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR01';
        $user->username = 'operator1';
        $this->actingAs($user);

        app(AuditLogger::class)->log(
            AuditLogger::EVENT_UPDATE,
            'Memperbarui user NIK 1',
            'user-management',
            oldData: ['is_active' => 0],
            newData: ['is_active' => 1],
            metadata: ['changes' => ['is_active' => ['from' => 0, 'to' => 1]]],
        );

        $this->assertDatabaseHas('tbl_audit_trail', [
            'user_id' => 0,
            'username' => 'operator1',
            'event' => 'update',
            'module' => 'user-management',
        ]);

        $row = AuditTrail::first();
        $this->assertEquals(['is_active' => 1], $row->new_data);
        $this->assertEquals(['changes' => ['is_active' => ['from' => 0, 'to' => 1]]], $row->metadata);
    }

    public function test_audit_trail_log_lists_records(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR02';
        $user->username = 'operator2';
        $this->actingAs($user);

        app(AuditLogger::class)->log(AuditLogger::EVENT_LOGIN, 'Pengguna berhasil masuk ke sistem', 'authentication');

        Livewire::test(AuditTrailLog::class)
            ->assertSet('totalLogs', 1)
            ->assertSee('authentication')
            ->assertSee('Pengguna berhasil masuk ke sistem');
    }

    public function test_event_filter_narrows_results(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR03';
        $user->username = 'operator3';
        $this->actingAs($user);

        app(AuditLogger::class)->log(AuditLogger::EVENT_LOGIN, 'Login', 'authentication');
        app(AuditLogger::class)->log(AuditLogger::EVENT_ACCESS, 'Akses', 'home');

        Livewire::test(AuditTrailLog::class)
            ->set('event', 'login')
            ->assertSet('totalLogs', 1);

        Livewire::test(AuditTrailLog::class)
            ->set('event', 'access')
            ->assertSet('totalLogs', 1);
    }

    public function test_certificate_requires_at_least_one_filter(): void
    {
        $controller = new AuditTrailCertificateController;

        try {
            $controller(Request::create('/user-management/audit-trail/certificate', 'GET'));
            $this->fail('Expected a 422 response when no filter is provided.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_certificate_renders_filtered_records(): void
    {
        app(AuditLogger::class)->log(AuditLogger::EVENT_LOGIN, 'Login sukses', 'authentication');
        app(AuditLogger::class)->log(AuditLogger::EVENT_ACCESS, 'Akses menu', 'home');

        $controller = new AuditTrailCertificateController;
        $request = Request::create('/user-management/audit-trail/certificate', 'GET', ['event' => 'login']);

        $html = $controller($request)->render();

        $this->assertStringContainsString('AUDIT TRAIL CERTIFICATE', $html);
        $this->assertStringContainsString('Login sukses', $html);
        $this->assertStringNotContainsString('/home', $html);
    }
}
