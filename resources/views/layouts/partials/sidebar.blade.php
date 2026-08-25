@php
    $approvalOpen = request()->routeIs('akakami.qaqc', 'akakami.approval', 'ranka.approval', 'abnormal.mtc.approval');
    $monitoringOpen = request()->routeIs('last.sequence', 'incoming.trace', 'realtime.production', 'productivity.process', 'manpower.productivity', 'carb.temper');
    $abnormalOpen = request()->routeIs('abnormal.report');
    $instructionOpen = request()->routeIs('instruction.rank.a');
    $manpowerOpen = request()->routeIs('manpower.control');
    $routingOpen = request()->routeIs('routing.process', 'routing.pairing.srp');
    $cycleTimeOpen = request()->routeIs('cycle-time.adjust');
    $documentProcessOpen = request()->routeIs('document.process.upload');
    $pttnEngineeringOpen = request()->routeIs('pttn.add-item-satuan', 'pttn.maker');
    $userManagementOpen = request()->routeIs('user-management.role-akses', 'user-management.user-control', 'user-management.qr-code');
    $qualityOpen = request()->routeIs('akakami.qaqc');
@endphp

<aside class="site-sidebar" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-brand">
      
        <span class="brand-text"></span>
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">▦</span><span class="nav-label">Daily Report</span>
        </a>
        <a href="{{ route('monthly.report') }}" class="sidebar-link {{ request()->routeIs('monthly.report') ? 'active' : '' }}">
            <span class="nav-icon">▤</span><span class="nav-label">Monthly Report</span>
        </a>
        <a href="{{ route('akakami.dashboard') }}" class="sidebar-link {{ request()->routeIs('akakami.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">◈</span><span class="nav-label">Akakami Dashboard</span>
        </a>
        <div class="sidebar-group {{ $userManagementOpen ? 'is-open' : '' }}" data-sidebar-group="user-management">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $userManagementOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">USER MANAGEMENT</span>
            </button>
            <div class="sidebar-group-items" {{ $userManagementOpen ? '' : 'hidden' }}>
                <a href="{{ route('user-management.role-akses') }}" class="sidebar-link {{ request()->routeIs('user-management.role-akses') ? 'active' : '' }}">
                    <span class="nav-icon">♙</span><span class="nav-label">Role Akses</span>
                </a>
                <a href="{{ route('user-management.user-control') }}" class="sidebar-link {{ request()->routeIs('user-management.user-control') ? 'active' : '' }}">
                    <span class="nav-icon">♟</span><span class="nav-label">User Control</span>
                </a>
                <a href="{{ route('user-management.qr-code') }}" class="sidebar-link {{ request()->routeIs('user-management.qr-code') ? 'active' : '' }}">
                    <span class="nav-icon">▣</span><span class="nav-label">Buat QR Code User</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $approvalOpen ? 'is-open' : '' }}" data-sidebar-group="approval">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $approvalOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">REPORT APPROVAL</span>
            </button>
            <div class="sidebar-group-items" {{ $approvalOpen ? '' : 'hidden' }}>
                <a href="{{ route('akakami.approval') }}" class="sidebar-link {{ request()->routeIs('akakami.approval') ? 'active' : '' }}">
                    <span class="nav-icon">✓</span><span class="nav-label">Aprv. Akakami</span>
                </a>
                <a href="{{ route('abnormal.mtc.approval') }}" class="sidebar-link {{ request()->routeIs('abnormal.mtc.approval') ? 'active' : '' }}">
                    <span class="nav-icon">!</span><span class="nav-label">Aprv. Abnormal Mtc</span>
                </a>
                <a href="{{ route('ranka.approval') }}" class="sidebar-link {{ request()->routeIs('ranka.approval') ? 'active' : '' }}">
                    <span class="nav-icon">★</span><span class="nav-label">Aprv. Rank A</span>
                </a>
            </div>
        </div>


        <div class="sidebar-group {{ $abnormalOpen ? 'is-open' : '' }}" data-sidebar-group="abnormal-report">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $abnormalOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">ABNORMAL REPORT</span>
            </button>
            <div class="sidebar-group-items" {{ $abnormalOpen ? '' : 'hidden' }}>
                <a href="{{ route('abnormal.report') }}" class="sidebar-link {{ request()->routeIs('abnormal.report') ? 'active' : '' }}">
                    <span class="nav-icon">!</span><span class="nav-label">Handling Abn. Report</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $instructionOpen ? 'is-open' : '' }}" data-sidebar-group="instruction-sheet">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $instructionOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">LEMBAR Rank A</span>
            </button>
            <div class="sidebar-group-items" {{ $instructionOpen ? '' : 'hidden' }}>
                <a href="{{ route('instruction.rank.a') }}" class="sidebar-link {{ request()->routeIs('instruction.rank.a') ? 'active' : '' }}">
                    <span class="nav-icon">★</span><span class="nav-label">Rank A</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $manpowerOpen ? 'is-open' : '' }}" data-sidebar-group="manpower">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $manpowerOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">MAN POWER</span>
            </button>
            <div class="sidebar-group-items" {{ $manpowerOpen ? '' : 'hidden' }}>
                <a href="{{ route('manpower.control') }}" class="sidebar-link {{ request()->routeIs('manpower.control') ? 'active' : '' }}">
                    <span class="nav-icon">♟</span><span class="nav-label">Man Power Control</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $routingOpen ? 'is-open' : '' }}" data-sidebar-group="routing">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $routingOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">ROUTING</span>
            </button>
            <div class="sidebar-group-items" {{ $routingOpen ? '' : 'hidden' }}>
                <a href="{{ route('routing.process') }}" class="sidebar-link {{ request()->routeIs('routing.process') ? 'active' : '' }}">
                    <span class="nav-icon">⇄</span><span class="nav-label">Susun Routing Proses</span>
                </a>
                <a href="{{ route('routing.pairing.srp') }}" class="sidebar-link {{ request()->routeIs('routing.pairing.srp') ? 'active' : '' }}">
                    <span class="nav-icon">⇆</span><span class="nav-label">Pairing Routing &amp; SRP</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $cycleTimeOpen ? 'is-open' : '' }}" data-sidebar-group="cycle-time">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $cycleTimeOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">CYCLE TIME</span>
            </button>
            <div class="sidebar-group-items" {{ $cycleTimeOpen ? '' : 'hidden' }}>
                <a href="{{ route('cycle-time.adjust') }}" class="sidebar-link {{ request()->routeIs('cycle-time.adjust') ? 'active' : '' }}">
                    <span class="nav-icon">◴</span><span class="nav-label">Adjust Cycle Time</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $documentProcessOpen ? 'is-open' : '' }}" data-sidebar-group="document-process">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $documentProcessOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">DOCUMENT PROCESS</span>
            </button>
            <div class="sidebar-group-items" {{ $documentProcessOpen ? '' : 'hidden' }}>
                <a href="{{ route('document.process.upload') }}" class="sidebar-link {{ request()->routeIs('document.process.upload') ? 'active' : '' }}">
                    <span class="nav-icon">▱</span><span class="nav-label">Upload WI Proses</span>
                </a>
            </div>
        </div>

        
        <div class="sidebar-group {{ $pttnEngineeringOpen ? 'is-open' : '' }}" data-sidebar-group="pttn-engineering">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $pttnEngineeringOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">PTTN ENGINEERING</span>
            </button>
            <div class="sidebar-group-items" {{ $pttnEngineeringOpen ? '' : 'hidden' }}>
                <a href="{{ route('pttn.add-item-satuan') }}" class="sidebar-link {{ request()->routeIs('pttn.add-item-satuan') ? 'active' : '' }}">
                    <span class="nav-icon">▤</span><span class="nav-label">Add Item Satuan</span>
                </a>
                <a href="{{ route('pttn.maker') }}" class="sidebar-link {{ request()->routeIs('pttn.maker') ? 'active' : '' }}">
                    <span class="nav-icon">▣</span><span class="nav-label">PTTN Maker</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $monitoringOpen ? 'is-open' : '' }}" data-sidebar-group="monitoring">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $monitoringOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">MONITORING</span>
            </button>
            <div class="sidebar-group-items" {{ $monitoringOpen ? '' : 'hidden' }}>
                <a href="{{ route('incoming.trace') }}" class="sidebar-link {{ request()->routeIs('incoming.trace') ? 'active' : '' }}">
                    <span class="nav-icon">△</span><span class="nav-label">Incoming Trace</span>
                </a>
                <a href="{{ route('realtime.production') }}" class="sidebar-link {{ request()->routeIs('realtime.production') ? 'active' : '' }}">
                    <span class="nav-icon">◷</span><span class="nav-label">Real Time Prod.</span>
                </a>
                <a href="{{ route('last.sequence') }}" class="sidebar-link {{ request()->routeIs('last.sequence') ? 'active' : '' }}">
                    <span class="nav-icon">◷</span><span class="nav-label">List Proses Terakhir</span>
                </a>
                <a href="{{ route('manpower.productivity') }}" class="sidebar-link {{ request()->routeIs('manpower.productivity') ? 'active' : '' }}">
                    <span class="nav-icon">♟</span><span class="nav-label">Perf. Man Power</span>
                </a>
                <a href="{{ route('productivity.process') }}" class="sidebar-link {{ request()->routeIs('productivity.process') ? 'active' : '' }}">
                    <span class="nav-icon">⚙</span><span class="nav-label">Perf. Prod.Process</span>
                </a>
                <a href="{{ route('carb.temper') }}" class="sidebar-link {{ request()->routeIs('carb.temper') ? 'active' : '' }}">
                    <span class="nav-icon">♨</span><span class="nav-label">Transisi Carb-Temper</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group {{ $qualityOpen ? 'is-open' : '' }}" data-sidebar-group="quality-control">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $qualityOpen ? 'true' : 'false' }}">
                <span class="group-chevron">›</span><span class="nav-label">QUALITY CONTROL</span>
            </button>
            <div class="sidebar-group-items" {{ $qualityOpen ? '' : 'hidden' }}>
                <a href="{{ route('akakami.qaqc') }}" class="sidebar-link {{ request()->routeIs('akakami.qaqc') ? 'active' : '' }}">
                    <span class="nav-icon">▣</span><span class="nav-label">Proses Akakami QAQC</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
