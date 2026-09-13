<?php

namespace Tests\Feature;

use App\Livewire\PicApprover;
use App\Services\MenuAuthorizationService;
use App\Services\PicApproverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PicApproverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tbl_user', function (Blueprint $table): void {
            $table->bigInteger('id_user')->primary();
            $table->string('username', 100)->unique();
            $table->string('pwd', 255)->default('');
            $table->integer('id_role')->default(1);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('tbljabatan', function (Blueprint $table): void {
            $table->string('id_jabatan', 3)->primary();
            $table->string('jabatan', 20);
        });

        Schema::create('tbl_pic_approve', function (Blueprint $table): void {
            $table->string('nik', 12)->primary();
            $table->string('jabatan', 30)->nullable();
            $table->string('class', 3)->nullable();
            $table->string('email', 30)->nullable();
            $table->string('no_hp', 30)->nullable();
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

        DB::table('tbljabatan')->insert([
            ['id_jabatan' => 'J04', 'jabatan' => 'General Manager'],
            ['id_jabatan' => 'J05', 'jabatan' => 'Manager'],
            ['id_jabatan' => 'J06', 'jabatan' => 'Asst. Manager'],
            ['id_jabatan' => 'J07', 'jabatan' => 'Supervisor'],
            ['id_jabatan' => 'J08', 'jabatan' => 'Asst.Spv'],
        ]);

        DB::table('tbl_user')->insert([
            ['id_user' => '13010001', 'username' => 'Hamka', 'pwd' => 'x', 'id_role' => 1, 'is_active' => true],
            ['id_user' => '13020002', 'username' => 'Agus', 'pwd' => 'x', 'id_role' => 1, 'is_active' => true],
        ]);
    }

    public function test_service_lists_references_and_cruds_approvers(): void
    {
        $service = app(PicApproverService::class);

        $this->assertCount(5, $service->jabatans());
        $this->assertCount(2, $service->users());

        $result = $service->approvers(1, '');
        $this->assertSame(0, $result['total']);

        $service->create(13010001, 'J04', 1, 'hamka@dowa-tht.co.id', '08111293900');

        $result = $service->approvers(1, '');
        $this->assertSame(1, $result['total']);
        $this->assertSame('13010001', $result['rows'][0]['nik']);
        $this->assertSame('Hamka', $result['rows'][0]['username']);
        $this->assertSame('General Manager', $result['rows'][0]['jabatan_name']);
        $this->assertSame('1', $result['rows'][0]['class']);
        $this->assertSame('hamka@dowa-tht.co.id', $result['rows'][0]['email']);

        $detail = $service->approver(13010001);
        $this->assertNotNull($detail);
        $this->assertSame(13010001, (int) $detail['id_user']);
        $this->assertSame('J04', $detail['jabatan']);

        $service->update(13010001, 'J05', 2, 'hamka@baru.co.id', '08111294999');
        $this->assertDatabaseHas('tbl_pic_approve', ['nik' => '13010001', 'class' => '2', 'email' => 'hamka@baru.co.id']);

        $service->delete(13010001);
        $this->assertDatabaseMissing('tbl_pic_approve', ['nik' => '13010001']);
    }

    public function test_service_rejects_unknown_or_duplicate_pic(): void
    {
        $service = app(PicApproverService::class);

        $this->expectException(RuntimeException::class);
        $service->create(999999, 'J04', 1, 'x@y.com', '08111');
        $this->expectException(RuntimeException::class);
        $service->create(13010001, 'J04', 1, 'hamka@dowa-tht.co.id', '08111293900');
        $service->create(13010001, 'J05', 2, 'ag@x.com', '08111');
        $this->expectException(RuntimeException::class);
        $service->create(13010001, 'J05', 2, 'ag@x.com', '08111');
    }

    public function test_component_saves_selects_updates_and_deletes(): void
    {
        $component = Livewire::test(PicApprover::class);

        $component->assertSet('totalApprovers', 0)
            ->assertSet('totalPages', 1);

        $this->assertCount(5, $component->get('jabatans'));
        $this->assertCount(2, $component->get('users'));

        $component->set('userId', '13010001')
            ->set('jabatan', 'J04')
            ->set('class', '1')
            ->set('email', 'hamka@dowa-tht.co.id')
            ->set('noHp', '08111293900')
            ->call('saveApprover');

        $this->assertDatabaseHas('tbl_pic_approve', [
            'nik' => '13010001',
            'jabatan' => 'J04',
            'class' => '1',
            'email' => 'hamka@dowa-tht.co.id',
            'no_hp' => '08111293900',
        ]);
        $component->assertSet('totalApprovers', 1);

        $component->call('selectApprover', 13010001)
            ->assertSet('userId', '13010001')
            ->assertSet('jabatan', 'J04')
            ->assertSet('class', '1')
            ->assertSet('email', 'hamka@dowa-tht.co.id')
            ->assertSet('noHp', '08111293900');

        $component->set('class', '2')
            ->call('saveApprover');
        $this->assertDatabaseHas('tbl_pic_approve', ['nik' => '13010001', 'class' => '2']);

        $component->call('selectApprover', 13010001)
            ->call('deleteApprover');
        $this->assertDatabaseMissing('tbl_pic_approve', ['nik' => '13010001']);
        $component->assertSet('totalApprovers', 0);
    }

    public function test_component_validates_required_fields(): void
    {
        Livewire::test(PicApprover::class)
            ->call('saveApprover')
            ->assertHasErrors(['userId', 'jabatan', 'class', 'email', 'noHp']);
    }

    public function test_pic_approver_menu_is_granted_only_to_full_access_user(): void
    {
        $service = app(MenuAuthorizationService::class);

        $this->assertTrue($service->canAccess(1, 'Didik', 'user-management-pic-approver'));
        $this->assertFalse($service->canAccess(2, 'Budi', 'user-management-pic-approver'));
    }
}