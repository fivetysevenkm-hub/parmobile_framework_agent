@php
    $approvalOpen = request()->routeIs('akakami.qaqc', 'akakami.approval', 'ranka.approval', 'abnormal.mtc.approval');
    $monitoringOpen = request()->routeIs('last.sequence', 'incoming.trace', 'realtime.production', 'productivity.process', 'manpower.productivity', 'carb.temper');
    $abnormalOpen = request()->routeIs('abnormal.report');
    $instructionOpen = request()->routeIs('instruction.rank.a');
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
                <span class="group-chevron">›</span><span class="nav-label">LEMBAR INSTRUKSI PERBAIKAN</span>
            </button>
            <div class="sidebar-group-items" {{ $instructionOpen ? '' : 'hidden' }}>
                <a href="{{ route('instruction.rank.a') }}" class="sidebar-link {{ request()->routeIs('instruction.rank.a') ? 'active' : '' }}">
                    <span class="nav-icon">★</span><span class="nav-label">Rank A</span>
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
