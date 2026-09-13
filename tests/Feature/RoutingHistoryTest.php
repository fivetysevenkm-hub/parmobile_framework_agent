<?php

namespace Tests\Feature;

use App\Livewire\RoutingHistoryChange;
use App\Services\PairingRoutingSrpService;
use App\Services\RoutingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class RoutingHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tblsequence', function (Blueprint $table): void {
            $table->string('id_seq', 3)->primary();
            $table->string('nm_sequence', 20);
        });

        Schema::create('tblrouting', function (Blueprint $table): void {
            $table->string('id_routing', 4)->primary();
            $table->string('nm_routing', 100)->nullable();
            for ($index = 1; $index <= 26; $index++) {
                $table->string('Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 3)->nullable();
            }
        });

        Schema::create('tblsrp', function (Blueprint $table): void {
            $table->string('no_srp', 10)->primary();
            $table->string('id_routing', 4)->nullable();
        });

        Schema::create('vw_srp_complete_routing', function (Blueprint $table): void {
            $table->string('no_srp', 10)->primary();
            $table->string('no_brg_asli', 50)->nullable();
            $table->string('nm_part', 100)->nullable();
        });

        Schema::create('tbl_routing_history', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('id_routing', 4)->index();
            $table->string('nm_routing', 100)->nullable();
            $table->unsignedTinyInteger('slot_no');
            $table->string('seq_before', 3)->nullable();
            $table->string('nama_before', 50)->nullable();
            $table->string('seq_after', 3)->nullable();
            $table->string('nama_after', 50)->nullable();
            $table->string('changed_by', 100)->nullable();
            $table->dateTime('changed_at')->index();
        });

        Schema::create('tbl_pairing_routing_history', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('no_srp', 10)->index();
            $table->string('no_part', 50)->nullable();
            $table->string('nm_part', 100)->nullable();
            $table->string('routing_before', 4)->nullable();
            $table->string('nama_before', 100)->nullable();
            $table->string('routing_after', 4)->nullable();
            $table->string('nama_after', 100)->nullable();
            $table->string('changed_by', 100)->nullable();
            $table->dateTime('changed_at')->index();
        });

        DB::table('tblsequence')->insert([
            ['id_seq' => 'S02', 'nm_sequence' => 'Setting'],
            ['id_seq' => 'S03', 'nm_sequence' => 'Pre Washing'],
            ['id_seq' => 'S05', 'nm_sequence' => 'GSN-Batch'],
            ['id_seq' => 'S06', 'nm_sequence' => 'HCQT-Batch'],
            ['id_seq' => 'S13', 'nm_sequence' => 'Post Washing'],
            ['id_seq' => 'S14', 'nm_sequence' => 'Inspection'],
            ['id_seq' => 'S20', 'nm_sequence' => 'Barashi Packing'],
            ['id_seq' => 'S26', 'nm_sequence' => 'Delivery'],
        ]);

        DB::table('tblrouting')->insert([
            [
                'id_routing' => 'R001',
                'nm_routing' => 'GSN BATCH',
                'Seq_01' => 'S02', 'Seq_02' => 'S03', 'Seq_03' => 'S05',
                'Seq_04' => 'S13', 'Seq_05' => 'S14', 'Seq_06' => 'S20', 'Seq_07' => 'S26',
            ],
            [
                'id_routing' => 'R005',
                'nm_routing' => 'HCQT-Batch-SB',
                'Seq_01' => 'S02', 'Seq_02' => 'S03', 'Seq_03' => 'S06',
                'Seq_04' => 'S13', 'Seq_05' => 'S14', 'Seq_06' => 'S15', 'Seq_07' => 'S26',
            ],
        ]);

        DB::table('tblsrp')->insert([
            ['no_srp' => '00000001', 'id_routing' => 'R001'],
        ]);

        DB::table('vw_srp_complete_routing')->insert([
            ['no_srp' => '00000001', 'no_brg_asli' => 'BR000353', 'nm_part' => 'Holder Assy'],
        ]);
    }

    public function test_routing_service_records_sequence_changes(): void
    {
        $service = app(RoutingService::class);

        $slots = [1 => 'S02', 2 => 'S03', 3 => 'S06', 4 => 'S13', 5 => 'S14', 6 => 'S20'];
        $service->save('R001', 'GSN BATCH', $slots, true);

        $this->assertDatabaseHas('tbl_routing_history', [
            'id_routing' => 'R001',
            'slot_no' => 3,
            'seq_before' => 'S05',
            'seq_after' => 'S06',
            'nama_before' => 'GSN-Batch',
            'nama_after' => 'HCQT-Batch',
            'changed_by' => 'System',
        ]);

        $this->assertDatabaseHas('tbl_routing_history', [
            'id_routing' => 'R001',
            'slot_no' => 7,
            'seq_before' => 'S26',
            'seq_after' => null,
            'nama_before' => 'Delivery',
        ]);

        $this->assertDatabaseHas('tblrouting', ['id_routing' => 'R001', 'Seq_03' => 'S06', 'Seq_07' => null]);
    }

    public function test_routing_service_skips_history_on_create_and_no_change(): void
    {
        $service = app(RoutingService::class);

        $service->save('R099', 'ROUTING BARU', [], false);
        $this->assertDatabaseCount('tbl_routing_history', 0);

        $service->save('R099', 'ROUTING BARU', [], true);
        $this->assertDatabaseCount('tbl_routing_history', 0);
    }

    public function test_pairing_service_records_routing_update(): void
    {
        $service = app(PairingRoutingSrpService::class);

        $service->updateRouting('00000001', 'R005');

        $this->assertDatabaseHas('tblsrp', ['no_srp' => '00000001', 'id_routing' => 'R005']);
        $this->assertDatabaseHas('tbl_pairing_routing_history', [
            'no_srp' => '00000001',
            'no_part' => 'BR000353',
            'nm_part' => 'Holder Assy',
            'routing_before' => 'R001',
            'nama_before' => 'GSN BATCH',
            'routing_after' => 'R005',
            'nama_after' => 'HCQT-Batch-SB',
            'changed_by' => 'System',
        ]);

        $service->updateRouting('00000001', 'R005');
        $this->assertDatabaseCount('tbl_pairing_routing_history', 1);
    }

    public function test_component_renders_safely_when_history_table_is_missing(): void
    {
        Schema::drop('tbl_routing_history');
        Schema::drop('tbl_pairing_routing_history');

        Livewire::test(RoutingHistoryChange::class)
            ->assertSet('activeTab', 'routing')
            ->assertSee('Belum ada data perubahan urutan routing');

        Livewire::test(RoutingHistoryChange::class)
            ->set('activeTab', 'pairing')
            ->assertSee('Belum ada data update pairing routing');
    }

    public function test_component_shows_both_history_tabs(): void
    {
        DB::table('tbl_routing_history')->insert([
            'id_routing' => 'R001',
            'nm_routing' => 'GSN BATCH',
            'slot_no' => 3,
            'seq_before' => 'S05',
            'nama_before' => 'GSN-Batch',
            'seq_after' => 'S06',
            'nama_after' => 'HCQT-Batch',
            'changed_by' => 'Didik',
            'changed_at' => '2026-09-13 09:15:00',
        ]);

        DB::table('tbl_pairing_routing_history')->insert([
            'no_srp' => '00000001',
            'no_part' => 'BR000353',
            'nm_part' => 'Holder Assy',
            'routing_before' => 'R001',
            'nama_before' => 'GSN BATCH',
            'routing_after' => 'R005',
            'nama_after' => 'HCQT-Batch-SB',
            'changed_by' => 'Didik',
            'changed_at' => '2026-09-13 10:30:00',
        ]);

        $component = Livewire::test(RoutingHistoryChange::class);

        $component->assertSet('activeTab', 'routing')
            ->assertSee('R001')
            ->assertSee('HCQT-Batch')
            ->assertSee('Didik');

        $component->set('activeTab', 'pairing')
            ->assertSee('00000001')
            ->assertSee('Holder Assy')
            ->assertSee('HCQT-Batch-SB')
            ->assertSee('Didik');
    }
}