<?php

return [
    'full_access_usernames' => ['Didik'],

    'default_access_keys' => [
        'daily-report',
        'monthly-report',
        'akakami-dashboard',
    ],

    'menus' => [
        ['key' => 'daily-report', 'name' => 'Daily Report', 'group' => 'Dashboard', 'route' => 'dashboard', 'aliases' => ['export.excel']],
        ['key' => 'monthly-report', 'name' => 'Monthly Report', 'group' => 'Dashboard', 'route' => 'monthly.report'],
        ['key' => 'akakami-dashboard', 'name' => 'Akakami Dashboard', 'group' => 'Dashboard', 'route' => 'akakami.dashboard'],
        ['key' => 'akakami-approval', 'name' => 'Aprv. Akakami', 'group' => 'REPORT APPROVAL', 'route' => 'akakami.approval', 'aliases' => ['akakami.print']],
        ['key' => 'abnormal-mtc-approval', 'name' => 'Aprv. Abnormal Mtc', 'group' => 'REPORT APPROVAL', 'route' => 'abnormal.mtc.approval'],
        ['key' => 'ranka-approval', 'name' => 'Aprv. Rank A', 'group' => 'REPORT APPROVAL', 'route' => 'ranka.approval', 'aliases' => ['rank.a.image']],
        ['key' => 'abnormal-report', 'name' => 'Handling Abn. Report', 'group' => 'ABNORMAL REPORT', 'route' => 'abnormal.report', 'aliases' => ['abnormal.report.print']],
        ['key' => 'instruction-rank-a', 'name' => 'Rank A', 'group' => 'LEMBAR Rank A', 'route' => 'instruction.rank.a', 'aliases' => ['rank.a.instruction.image', 'rank.a.instruction.print']],
        ['key' => 'manpower-control', 'name' => 'Man Power Control', 'group' => 'MAN POWER', 'route' => 'manpower.control'],
        ['key' => 'routing-process', 'name' => 'Susun Routing Proses', 'group' => 'ROUTING', 'route' => 'routing.process'],
        ['key' => 'routing-pairing-srp', 'name' => 'Pairing Routing & SRP', 'group' => 'ROUTING', 'route' => 'routing.pairing.srp'],
        ['key' => 'cycle-time-adjust', 'name' => 'Adjust Cycle Time', 'group' => 'CYCLE TIME', 'route' => 'cycle-time.adjust'],
        ['key' => 'document-process-upload', 'name' => 'Upload WI Proses', 'group' => 'DOCUMENT PROCESS', 'route' => 'document.process.upload', 'aliases' => ['document.process.pdf']],
        ['key' => 'pttn-add-item-satuan', 'name' => 'Add Item Satuan', 'group' => 'PTTN ENGINEERING', 'route' => 'pttn.add-item-satuan'],
        ['key' => 'pttn-maker', 'name' => 'PTTN Maker', 'group' => 'PTTN ENGINEERING', 'route' => 'pttn.maker', 'aliases' => ['pttn.image']],
        ['key' => 'pttn-pairing-srp', 'name' => 'Pairing PTTN to SRP', 'group' => 'PTTN ENGINEERING', 'route' => 'pttn.pairing.srp'],
        ['key' => 'pttn-omes-input', 'name' => 'Input PTTN Omes', 'group' => 'PTTN OPERATOR MESIN', 'route' => 'pttn.omes.input'],
        ['key' => 'incoming-trace', 'name' => 'Incoming Trace', 'group' => 'MONITORING', 'route' => 'incoming.trace', 'aliases' => ['incoming.trace.export']],
        ['key' => 'realtime-production', 'name' => 'Real Time Prod.', 'group' => 'MONITORING', 'route' => 'realtime.production', 'aliases' => ['realtime.production.export']],
        ['key' => 'last-sequence', 'name' => 'List Proses Terakhir', 'group' => 'MONITORING', 'route' => 'last.sequence', 'aliases' => ['last.sequence.export']],
        ['key' => 'manpower-productivity', 'name' => 'Perf. Man Power', 'group' => 'MONITORING', 'route' => 'manpower.productivity', 'aliases' => ['manpower.productivity.export', 'manpower.productivity.certificate']],
        ['key' => 'productivity-process', 'name' => 'Perf. Prod.Process', 'group' => 'MONITORING', 'route' => 'productivity.process', 'aliases' => ['productivity.process.export']],
        ['key' => 'carb-temper', 'name' => 'Transisi Carb-Temper', 'group' => 'MONITORING', 'route' => 'carb.temper'],
        ['key' => 'akakami-qaqc', 'name' => 'Proses Akakami QAQC', 'group' => 'QUALITY CONTROL', 'route' => 'akakami.qaqc', 'aliases' => ['akakami.export']],
        ['key' => 'user-management-role', 'name' => 'Role Akses', 'group' => 'USER MANAGEMENT', 'route' => 'user-management.role-akses'],
        ['key' => 'user-management-control', 'name' => 'User Control', 'group' => 'USER MANAGEMENT', 'route' => 'user-management.user-control'],
        ['key' => 'user-management-qr', 'name' => 'Buat QR Code User', 'group' => 'USER MANAGEMENT', 'route' => 'user-management.qr-code', 'aliases' => ['user-management.qr-code.print']],
        ['key' => 'user-management-authorization', 'name' => 'Otorisasi Menu', 'group' => 'USER MANAGEMENT', 'route' => 'user-management.menu-authorization'],
    ],
];
