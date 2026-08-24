<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("SHOW COLUMNS FROM tblakakami WHERE Field IN ('pic_handler', 'pic', 'pic_dept', 'status_flpp', 'tgl_approved')");
foreach ($columns as $column) {
    echo $column->Field.'|'.$column->Type.'|'.$column->Null.'|'.$column->Default."\n";
}
$userColumns = DB::select("SHOW COLUMNS FROM tbl_user WHERE Field IN ('id_user', 'username', 'name')");
foreach ($userColumns as $column) {
    echo 'USER|'.$column->Field.'|'.$column->Type."\n";
}
