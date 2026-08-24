<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = Illuminate\Support\Facades\DB::select(
    'SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
    ['vw_exportexcel_akakami']
);
foreach ($columns as $column) {
    echo $column->COLUMN_NAME.'|'.$column->DATA_TYPE.PHP_EOL;
}

try {
    $count = Illuminate\Support\Facades\DB::table('vw_exportexcel_akakami')->count();
    echo 'count='.$count.PHP_EOL;
} catch (Throwable $exception) {
    echo 'count_error='.get_class($exception).PHP_EOL;
}
