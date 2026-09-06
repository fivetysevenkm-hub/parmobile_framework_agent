<?php

namespace Tests\Feature;

use App\Livewire\MachineMapping;
use App\Models\LegacyUser;
use App\Support\SchemaCache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MachineMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SchemaCache::flush();

        Schema::create('tblsequence', function (Blueprint $table): void {
            $table->string('id_seq', 10)->primary();
            $table->string('nm_sequence', 100)->nullable();
        });

        Schema::create('tblmesin', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('no_mesin', 30);
            $table->string('mesin', 100)->nullable();
        });

        Schema::create('tbl_machine_mapping', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('id_seq', 10);
            $table->string('no_mesin', 30);
            $table->timestamps();
            $table->unique(['id_seq', 'no_mesin']);
        });

        DB::table('tblsequence')->insert([
            ['id_seq' => 'S02', 'nm_sequence' => 'GSN'],
            ['id_seq' => 'S05', 'nm_sequence' => 'Tempering'],
        ]);

        DB::table('tblmesin')->insert([
            ['no_mesin' => 'MC001', 'mesin' => 'GSN 01'],
            ['no_mesin' => 'MC002', 'mesin' => 'GSN 02'],
            ['no_mesin' => 'MC003', 'mesin' => 'Temper 01'],
        ]);
    }

    protected function actingAsDidik(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR-DIDIK';
        $user->username = 'Didik';
        $this->actingAs($user);
    }

    public function test_component_renders_sequences_and_machines(): void
    {
        $this->actingAsDidik();

        Livewire::test(MachineMapping::class)
            ->assertOk()
            ->assertSet('selectedSequence', 'S02')
            ->assertSee('GSN')
            ->assertSee('GSN 01');
    }

    public function test_toggle_and_save_mapping_persists(): void
    {
        $this->actingAsDidik();

        Livewire::test(MachineMapping::class)
            ->call('toggleMachine', 'MC001')
            ->call('toggleMachine', 'MC002')
            ->assertCount('selectedMachines', 2)
            ->call('save')
            ->assertHasNoErrors();

        $saved = DB::table('tbl_machine_mapping')->where('id_seq', 'S02')->orderBy('no_mesin')->pluck('no_mesin')->all();
        $this->assertSame(['MC001', 'MC002'], $saved);
    }

    public function test_clear_selection_and_delete_mapping(): void
    {
        $this->actingAsDidik();

        Livewire::test(MachineMapping::class)
            ->call('toggleMachine', 'MC001')
            ->call('save');

        Livewire::test(MachineMapping::class)
            ->call('clearSelection')
            ->assertCount('selectedMachines', 0)
            ->call('save');

        $this->assertSame(0, DB::table('tbl_machine_mapping')->where('id_seq', 'S02')->count());
    }

    public function test_filters_machines_and_selects_all_visible(): void
    {
        $this->actingAsDidik();

        Livewire::test(MachineMapping::class)
            ->set('machineSearch', 'GSN')
            ->call('selectAllVisible')
            ->assertCount('selectedMachines', 2);
    }
}
