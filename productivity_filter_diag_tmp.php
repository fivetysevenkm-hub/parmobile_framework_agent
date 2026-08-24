<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$processes = Illuminate\Support\Facades\DB::table('tbl_seq_all')->whereNotNull('nm_seq')->where('nm_seq', '<>', '')->select('nm_seq')->distinct()->orderBy('nm_seq')->limit(20)->pluck('nm_seq');
echo "PROCESSES\n";
foreach ($processes as $process) echo '['.$process.']\n';

foreach ([['Setting','SNF1','2026-08-21','2026-08-24'], ['Setting','','2026-08-21','2026-08-24'], ['','', '2026-08-21','2026-08-24']] as [$process,$machine,$from,$to]) {
    $q = Illuminate\Support\Facades\DB::table('tbl_seq_all')->where('nm_seq', $process)->when($machine !== '', fn($q) => $q->where('no_mesin',$machine))->where('start_time','>=',$from.' 00:00:00')->where('start_time','<',$to.' 00:00:00');
    echo "COUNT process={$process} machine={$machine} range={$from}..{$to}: ".$q->count()."\n";
}

$latest = Illuminate\Support\Facades\DB::table('tbl_seq_all')->select('nm_seq','no_mesin','start_time','no_lot')->whereNotNull('start_time')->orderByDesc('start_time')->limit(3)->get();
echo "LATEST\n";
foreach ($latest as $row) echo json_encode($row)."\n";
