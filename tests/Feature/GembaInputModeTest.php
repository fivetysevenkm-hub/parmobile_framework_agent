<?php

namespace Tests\Feature;

use App\Livewire\GembaInput;
use App\Models\LegacyUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class GembaInputModeTest extends TestCase
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

        Schema::create('tbl_user_gemba_mode', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('id_user', 50)->nullable()->index();
            $table->string('mode', 20)->default('berpindah');
            $table->string('id_seq', 10)->nullable();
            $table->string('nm_sequence', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('tblsequence', function (Blueprint $table): void {
            $table->string('id_seq', 3)->primary();
            $table->string('nm_sequence', 20);
        });

        Schema::create('tbllotdaily', function (Blueprint $table): void {
            $table->string('no_lot', 50)->primary();
            $table->string('no_srps', 50)->nullable();
            $table->string('no_sj_cust', 50)->nullable();
            $table->string('no_lot_cust', 50)->nullable();
            $table->string('qty', 50)->nullable();
        });

        Schema::create('tblsrp', function (Blueprint $table): void {
            $table->string('no_srp', 50)->primary();
            $table->string('id_routing', 50)->nullable();
            $table->string('no_parts', 50)->nullable();
            $table->string('no_customers', 50)->nullable();
            $table->string('no_process', 50)->nullable();
        });

        Schema::create('tblrouting', function (Blueprint $table): void {
            $table->string('id_routing', 50)->primary();
            $table->string('Seq_01', 10)->nullable();
            $table->string('Seq_02', 10)->nullable();
            $table->string('Seq_03', 10)->nullable();
        });

        Schema::create('tbl_seq_01', function (Blueprint $table): void {
            $table->string('no_lot', 50)->primary();
            $table->string('start_time', 50)->nullable();
            $table->string('finish_time', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->string('tray_no', 50)->nullable();
            $table->string('tray_nokecil', 50)->nullable();
            $table->string('no_mesin', 50)->nullable();
            $table->string('pic_01', 50)->nullable();
            $table->string('pic_02', 50)->nullable();
            $table->string('pic_03', 50)->nullable();
            $table->string('pic_04', 50)->nullable();
            $table->string('akakami', 50)->nullable();
            $table->string('ojt', 50)->nullable();
            $table->string('hold', 50)->nullable();
            $table->string('finish', 50)->nullable();
        });

        Schema::create('tbl_seq_02', function (Blueprint $table): void {
            $table->string('no_lot', 50)->primary();
            $table->string('start_time', 50)->nullable();
            $table->string('finish_time', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->string('tray_no', 50)->nullable();
            $table->string('tray_nokecil', 50)->nullable();
            $table->string('no_mesin', 50)->nullable();
            $table->string('pic_01', 50)->nullable();
            $table->string('pic_02', 50)->nullable();
            $table->string('pic_03', 50)->nullable();
            $table->string('pic_04', 50)->nullable();
            $table->string('akakami', 50)->nullable();
            $table->string('ojt', 50)->nullable();
            $table->string('hold', 50)->nullable();
            $table->string('finish', 50)->nullable();
        });

        Schema::create('tbl_seq_all', function (Blueprint $table): void {
            $table->id();
            $table->string('no_lot', 50)->nullable();
            $table->string('no_srp', 50)->nullable();
            $table->integer('seq')->nullable();
            $table->string('id_seq', 50)->nullable();
            $table->string('nm_seq', 50)->nullable();
            $table->string('start_time', 50)->nullable();
            $table->string('finish_time', 50)->nullable();
            $table->string('duration', 50)->nullable();
            $table->string('finish', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_validator', function (Blueprint $table): void {
            $table->id();
            $table->string('no_lot', 50)->unique();
            $table->string('no_srp', 50)->nullable();
            $table->string('id_routing', 50)->nullable();
            $table->string('Current_seq_code', 50)->nullable();
            $table->string('next_seq_code', 50)->nullable();
            $table->string('Curr_process', 50)->nullable();
            $table->string('next_process', 50)->nullable();
            $table->integer('current_seq_count')->nullable();
            $table->integer('next_seq_count')->nullable();
            $table->integer('total_seq_count')->nullable();
            $table->integer('total_seq_remain')->nullable();
            $table->string('finish', 10)->default('no');
            $table->timestamps();
        });

        Schema::create('tblkaryawan', function (Blueprint $table): void {
            $table->string('nik', 50)->primary();
            $table->string('nama_kary', 100)->nullable();
            $table->string('ojt_a', 20)->nullable();
            $table->string('class_01', 20)->nullable();
        });

        Schema::create('tbl_pdf_document', function (Blueprint $table): void {
            $table->id();
            $table->string('doc_no', 100)->nullable();
            $table->string('doc_name', 200)->nullable();
            $table->string('no_srp', 50)->nullable();
            $table->string('id_routing', 50)->nullable();
            $table->string('sequence', 10)->nullable();
            $table->string('file_path', 200)->nullable();
            $table->string('upload_by', 50)->nullable();
            $table->dateTime('upload_date')->nullable();
        });

        // Insert sample sequence master data
        DB::table('tblsequence')->insert([
            ['id_seq' => '01', 'nm_sequence' => 'CARBURIZING'],
            ['id_seq' => '02', 'nm_sequence' => 'TEMPERING'],
            ['id_seq' => '03', 'nm_sequence' => 'SHOT BLAST'],
        ]);

        // Insert sample routing and lot data
        DB::table('tblrouting')->insert([
            'id_routing' => 'ROUT-001',
            'Seq_01' => '01',
            'Seq_02' => '02',
            'Seq_03' => '03',
        ]);

        DB::table('tblsrp')->insert([
            'no_srp' => 'SRP-001',
            'id_routing' => 'ROUT-001',
        ]);

        DB::table('tbllotdaily')->insert([
            'no_lot' => 'LOT-12345',
            'no_srps' => 'SRP-001',
            'qty' => '100',
        ]);
    }

    public function test_default_mode_is_berpindah(): void
    {
        Livewire::test(GembaInput::class)
            ->assertSet('processMode', 'berpindah')
            ->assertSet('selectedSequence', '')
            ->assertSee('Mode Posisi Berpindah (default)')
            ->assertSee('Mode Posisi Tetap')
            ->assertDontSee('-- Pilih Nama Proses --');
    }

    public function test_switching_mode_to_tetap_shows_combo_and_auto_saves(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR01';
        $user->username = 'operator1';
        $this->actingAs($user);

        Livewire::test(GembaInput::class)
            ->set('processMode', 'tetap')
            ->assertSee('-- Pilih Nama Proses --')
            ->assertSee('CARBURIZING (01)')
            ->set('selectedSequence', '01');

        $this->assertDatabaseHas('tbl_user_gemba_mode', [
            'username' => 'operator1',
            'mode' => 'tetap',
            'id_seq' => '01',
            'nm_sequence' => 'CARBURIZING',
        ]);
    }

    public function test_loads_saved_user_preference_on_mount(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR02';
        $user->username = 'operator2';
        $this->actingAs($user);

        DB::table('tbl_user_gemba_mode')->insert([
            'username' => 'operator2',
            'id_user' => 'USR02',
            'mode' => 'tetap',
            'id_seq' => '02',
            'nm_sequence' => 'TEMPERING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(GembaInput::class)
            ->assertSet('processMode', 'tetap')
            ->assertSet('selectedSequence', '02');
    }

    public function test_mode_berpindah_loads_lot_normally(): void
    {
        Livewire::test(GembaInput::class)
            ->set('processMode', 'berpindah')
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSet('noLot', 'LOT-12345')
            ->assertSet('sequenceNo', 1)
            ->assertSee('CARBURIZING');
    }

    public function test_mode_tetap_with_matching_process_loads_lot_normally(): void
    {
        Livewire::test(GembaInput::class)
            ->set('processMode', 'tetap')
            ->set('selectedSequence', '01')
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSet('noLot', 'LOT-12345')
            ->assertSet('sequenceNo', 1)
            ->assertSee('CARBURIZING');
    }

    public function test_mode_tetap_with_mismatching_process_triggers_jumping_process_and_resets(): void
    {
        Livewire::test(GembaInput::class)
            ->set('processMode', 'tetap')
            ->set('selectedSequence', '02') // Expecting TEMPERING (02), but lot is at CARBURIZING (01)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertDispatched('jumping-process-alert', message: 'Anda Sedang Jumping Process')
            ->assertSet('flashMessage', 'Anda Sedang Jumping Process')
            ->assertSet('flashType', 'warning')
            ->assertSet('noLot', '') // Form reset
            ->assertSet('sequenceNo', 0)
            ->assertSet('processMode', 'tetap') // Mode preference preserved
            ->assertSet('selectedSequence', '02'); // Selected sequence preserved
    }

    public function test_button_activation_cycle_and_reset_on_finish(): void
    {
        DB::table('tblkaryawan')->insert([
            'nik' => 'KARY01',
            'nama_kary' => 'Operator Test',
            'ojt_a' => 'no',
            'class_01' => '01',
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('processMode', 'berpindah')
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput');

        // Condition Before Start
        $this->assertTrue($component->instance()->canStart());
        $this->assertFalse($component->instance()->canAkakami());
        $this->assertFalse($component->instance()->canFinish());
        $this->assertFalse($component->instance()->canReportAbnormal());

        // Add Operator and Tray, then Start
        $component->set('operatorNik', 'KARY01')
            ->call('addOperator')
            ->set('trayNo', 'TRAY-01')
            ->call('startProcess');

        // Condition After Start (Running)
        $this->assertFalse($component->instance()->canStart());
        $this->assertTrue($component->instance()->canAkakami());
        $this->assertTrue($component->instance()->canFinish());
        $this->assertTrue($component->instance()->canReportAbnormal());

        // Finish Process
        $component->call('finishProcess');

        // Condition After Finish (Reset as clean cycle)
        $this->assertEquals('', $component->get('noLot'));
        $this->assertEquals(0, $component->get('sequenceNo'));
        $this->assertEmpty($component->get('context'));
        $this->assertEmpty($component->get('operators'));
        $this->assertEquals('', $component->get('trayNo'));
        $this->assertFalse($component->instance()->canStart());
        $this->assertFalse($component->instance()->canAkakami());
        $this->assertFalse($component->instance()->canFinish());
        $this->assertFalse($component->instance()->canReportAbnormal());
    }

    public function test_opening_from_lot_verifikator_matches_button_cycle_and_cleans_records(): void
    {
        $component = Livewire::test(GembaInput::class, [
            'noLot' => 'LOT-12345',
            'sequenceNo' => 1,
        ]);

        $component->assertSet('noLot', 'LOT-12345')
            ->assertSet('sequenceNo', 1);

        // Buttons must match Gemba Input cycle
        $this->assertTrue($component->instance()->canStart());
        $this->assertFalse($component->instance()->canAkakami());
        $this->assertFalse($component->instance()->canFinish());
        $this->assertFalse($component->instance()->canReportAbnormal());
    }

    public function test_opening_from_lot_verifikator_triggers_jumping_process_if_mode_tetap_mismatched(): void
    {
        $user = new LegacyUser;
        $user->id_user = 'USR03';
        $user->username = 'operator3';
        $this->actingAs($user);

        DB::table('tbl_user_gemba_mode')->insert([
            'username' => 'operator3',
            'id_user' => 'USR03',
            'mode' => 'tetap',
            'id_seq' => '02', // TEMPERING
            'nm_sequence' => 'TEMPERING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Open sequence 1 (CARBURIZING) from LotVerifikator while mode tetap is set to TEMPERING
        Livewire::test(GembaInput::class, [
            'noLot' => 'LOT-12345',
            'sequenceNo' => 1,
        ])
            ->assertDispatched('jumping-process-alert', message: 'Anda Sedang Jumping Process')
            ->assertSet('flashMessage', 'Anda Sedang Jumping Process')
            ->assertSet('noLot', '')
            ->assertSet('sequenceNo', 0);
    }

    public function test_scanning_new_lot_cleans_previous_inputs_first(): void
    {
        // Add a second lot
        DB::table('tbllotdaily')->insert([
            'no_lot' => 'LOT-67890',
            'no_srps' => 'SRP-001',
            'qty' => '50',
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->set('trayNo', 'TRAY-OLD-123')
            ->set('traySmall', 'TRAY-SM-123');

        $this->assertEquals('TRAY-OLD-123', $component->get('trayNo'));

        // Scan second lot: old tray inputs should be cleaned first
        $component->set('scanInput', 'LOT-67890')
            ->call('processScanInput')
            ->assertSet('noLot', 'LOT-67890')
            ->assertSet('trayNo', '')
            ->assertSet('traySmall', '');
    }

    public function test_stopwatch_markup_and_persistence_across_lot_switches(): void
    {
        // Add operator for start
        DB::table('tblkaryawan')->insert([
            'nik' => 'KARY99',
            'nama_kary' => 'Operator Stopwatch',
            'ojt_a' => 'no',
            'class_01' => '01',
        ]);

        // Add a second lot
        DB::table('tbllotdaily')->insert([
            'no_lot' => 'LOT-67890',
            'no_srps' => 'SRP-001',
            'qty' => '50',
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSee('STOPWATCH')
            ->assertSee('STANDBY');

        $this->assertEquals('', $component->instance()->stopwatchStartTime());

        // Start process
        $component->set('operatorNik', 'KARY99')
            ->call('addOperator')
            ->set('trayNo', 'TRAY-SW-1')
            ->call('startProcess')
            ->assertSee('RUNNING');

        $startTimeIso = $component->instance()->stopwatchStartTime();
        $this->assertNotEmpty($startTimeIso);

        // Switch to another lot
        $component->set('scanInput', 'LOT-67890')
            ->call('processScanInput')
            ->assertSee('STANDBY');

        $this->assertEquals('', $component->instance()->stopwatchStartTime());

        // Call first lot back: it remembers the exact start time and is running!
        $component->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSee('RUNNING');

        $this->assertEquals($startTimeIso, $component->instance()->stopwatchStartTime());
    }

    public function test_tray_numbers_auto_copied_from_initial_process_to_subsequent_processes_and_editable(): void
    {
        // 1. Process 1 has tray numbers recorded
        DB::table('tbl_seq_01')->insert([
            'no_lot' => 'LOT-12345',
            'tray_no' => 'TRAY-BESAR-99',
            'tray_nokecil' => 'TRAY-KECIL-99',
            'start_time' => '2026-08-29 00:00:00',
            'finish_time' => '2026-08-29 00:30:00',
            'finish' => 'yes',
        ]);

        // 2. Open process 2 (Sequence 2) from LotVerifikator
        $component = Livewire::test(GembaInput::class, [
            'noLot' => 'LOT-12345',
            'sequenceNo' => 2,
        ]);

        // Should automatically copy tray_no and tray_nokecil from process 1
        $this->assertEquals('TRAY-BESAR-99', $component->get('trayNo'));
        $this->assertEquals('TRAY-KECIL-99', $component->get('traySmall'));

        // 3. User can edit the tray numbers
        $component->set('trayNo', 'TRAY-BESAR-NEW')
            ->set('traySmall', 'TRAY-KECIL-NEW');

        $this->assertEquals('TRAY-BESAR-NEW', $component->get('trayNo'));
        $this->assertEquals('TRAY-KECIL-NEW', $component->get('traySmall'));
    }

    public function test_button_states_when_start_time_not_null_and_finish_time_null(): void
    {
        // 1. Lot was started previously, but finish_time is null
        DB::table('tbl_seq_01')->insert([
            'no_lot' => 'LOT-12345',
            'tray_no' => 'TRAY-01',
            'start_time' => '2026-08-29 18:00:00',
            'finish_time' => null,
            'hold' => 'no',
            'finish' => 'no',
            'pic_01' => 'KARY99',
        ]);

        // 2. Load lot in GembaInput
        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput');

        // Prosedur:
        // - Tombol Start: non-aktif (false)
        // - Tombol Akakami: aktif (true)
        // - Tombol Finish: aktif (true)
        // - Tombol Abnormal Rep: aktif (true)
        $this->assertFalse($component->instance()->canStart());
        $this->assertTrue($component->instance()->canAkakami());
        $this->assertTrue($component->instance()->canFinish());
        $this->assertTrue($component->instance()->canReportAbnormal());
    }

    public function test_legacy_zero_datetime_finish_time_is_not_treated_as_finished(): void
    {
        // In legacy MySQL, start_time is set, and finish_time is default '0000-00-00 00:00:00'
        DB::table('tbl_seq_01')->insert([
            'no_lot' => 'LOT-12345',
            'tray_no' => 'TRAY-01',
            'start_time' => '2026-08-29 18:50:08',
            'finish_time' => '0000-00-00 00:00:00',
            'hold' => 'no',
            'finish' => 'no',
            'pic_01' => 'KARY99',
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput');

        $this->assertTrue($component->instance()->isProcessStarted());
        $this->assertFalse($component->instance()->isProcessFinished());
        $this->assertFalse($component->instance()->canStart());
        $this->assertTrue($component->instance()->canAkakami());
        $this->assertTrue($component->instance()->canFinish());
        $this->assertTrue($component->instance()->canReportAbnormal());

        $this->assertNotEmpty($component->instance()->stopwatchStartTime());
        $this->assertNotNull($component->instance()->stopwatchStartMs());
        $this->assertEquals('', $component->instance()->stopwatchFinishTime());
        $this->assertNull($component->instance()->stopwatchFinishMs());

        $component->assertSee('RUNNING')
            ->assertDontSee('data-is-finished="1"');
    }

    public function test_lot_not_in_tbllotdaily_shows_warning_notification(): void
    {
        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-TIDAK-ADA')
            ->call('processScanInput');

        $component->assertSee('Lot tersebut tidak ada')
            ->assertSet('flashType', 'warning');
    }

    public function test_delivered_lot_in_tbl_seq_all_shows_warning_notification(): void
    {
        // 1. Lot exists in tbllotdaily
        DB::table('tbllotdaily')->insert([
            'no_lot' => 'LOT-DELIVERED',
            'no_srps' => 'SRP-001',
            'qty' => '100',
        ]);

        // 2. In tbl_seq_all, lot status is S26 (delivery) with finish = yes
        DB::table('tbl_seq_all')->insert([
            'no_lot' => 'LOT-DELIVERED',
            'no_srp' => 'SRP-001',
            'seq' => 10,
            'id_seq' => 'S26',
            'nm_seq' => 'Delivery',
            'finish' => 'yes',
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-DELIVERED')
            ->call('processScanInput');

        $component->assertSee('Lot Sudah selesai proses &amp; telah di kirim ke customer', false)
            ->assertSet('flashType', 'warning');
    }

    public function test_wi_button_appears_when_wi_pdf_exists_for_lot_process(): void
    {
        DB::table('tbl_pdf_document')->insert([
            'doc_no' => 'uuid-1',
            'doc_name' => 'IK_SRP-001_CARBURIZING.pdf',
            'no_srp' => 'SRP-001',
            'id_routing' => 'ROUT-001',
            'sequence' => '01',
            'file_path' => 'wi/SRP-001/ROUT-001/01/IK_SRP-001_CARBURIZING.pdf',
            'upload_by' => 'USR01',
            'upload_date' => now(),
        ]);

        Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSet('hasWorkInstruction', true)
            ->assertSet('wiDocumentId', 1)
            ->assertSee('INPUT WI');
    }

    public function test_wi_button_hidden_when_no_wi_pdf_exists_for_lot_process(): void
    {
        Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput')
            ->assertSet('hasWorkInstruction', false)
            ->assertSet('wiDocumentId', 0)
            ->assertDontSee('INPUT WI');
    }

    public function test_wi_button_uses_latest_document_for_the_active_sequence(): void
    {
        DB::table('tbl_pdf_document')->insert([
            ['doc_no' => 'uuid-old', 'doc_name' => 'IK_SRP-001_CARBURIZING_OLD.pdf', 'no_srp' => 'SRP-001', 'id_routing' => 'ROUT-001', 'sequence' => '01', 'file_path' => 'wi/SRP-001/ROUT-001/01/old.pdf', 'upload_by' => 'USR01', 'upload_date' => now()->subDay()],
            ['doc_no' => 'uuid-new', 'doc_name' => 'IK_SRP-001_CARBURIZING_NEW.pdf', 'no_srp' => 'SRP-001', 'id_routing' => 'ROUT-001', 'sequence' => '01', 'file_path' => 'wi/SRP-001/ROUT-001/01/new.pdf', 'upload_by' => 'USR01', 'upload_date' => now()],
            ['doc_no' => 'uuid-seq02', 'doc_name' => 'IK_SRP-001_TEMPERING.pdf', 'no_srp' => 'SRP-001', 'id_routing' => 'ROUT-001', 'sequence' => '02', 'file_path' => 'wi/SRP-001/ROUT-001/02/temper.pdf', 'upload_by' => 'USR01', 'upload_date' => now()],
        ]);

        $component = Livewire::test(GembaInput::class)
            ->set('scanInput', 'LOT-12345')
            ->call('processScanInput');

        // Active process is sequence 01 (CARBURIZING); must pick the latest doc for 01, ignore sequence 02.
        $this->assertTrue($component->instance()->hasWorkInstruction);
        $this->assertSame(2, $component->get('wiDocumentId'));
    }
}
