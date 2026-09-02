@php
    $approvalOpen = request()->routeIs('akakami.qaqc', 'akakami.approval', 'ranka.approval', 'abnormal.mtc.approval');
    $monitoringOpen = request()->routeIs('last.sequence', 'incoming.trace', 'realtime.production', 'productivity.process', 'manpower.productivity', 'carb.temper');
    $abnormalOpen = request()->routeIs('abnormal.report');
    $instructionOpen = request()->routeIs('instruction.rank.a');
    $manpowerOpen = request()->routeIs('manpower.control');
    $routingOpen = request()->routeIs('routing.process', 'routing.pairing.srp');
    $cycleTimeOpen = request()->routeIs('cycle-time.adjust');
    $documentProcessOpen = request()->routeIs('document.process.upload');
    $lotVerifikatorOpen = request()->routeIs('lot.verifikator', 'gemba.input');
    $pttnEngineeringOpen = request()->routeIs('pttn.add-item-satuan', 'pttn.maker', 'pttn.pairing.srp');
    $pttnOperatorOpen = request()->routeIs('pttn.omes.input');
    $userManagementOpen = request()->routeIs('user-management.role-akses', 'user-management.user-control', 'user-management.qr-code', 'user-management.menu-authorization', 'user-management.hide-menu', 'user-management.audit-trail', 'user-management.customer-portal-users');
    $qualityOpen = request()->routeIs('akakami.qaqc');

    $sessionUser = auth()->user();
    $sessionUserId = $sessionUser ? (int) $sessionUser->getAuthIdentifier() : (int) (session('id_user') ?: 0);
    $sessionUsername = $sessionUser?->username ?: session('username');
    $authorizationService = app(\App\Services\MenuAuthorizationService::class);
    $menuAccess = $authorizationService->accessMap($sessionUserId, $sessionUsername);
    $menuVisibility = $authorizationService->visibilityMap($sessionUserId, $sessionUsername);
    $parentVisible = fn (string $groupName): bool => (bool) ($menuVisibility[$authorizationService->groupKey($groupName)] ?? true)
        && $authorizationService->groupHasVisibleChild($groupName, $menuVisibility);
    $menuLink = function (string $key, string $route, string $label, string $icon) use ($menuAccess, $menuVisibility): string {
        if (! (bool) ($menuVisibility[$key] ?? true)) {
            return '';
        }
        $allowed = (bool) ($menuAccess[$key] ?? false);
        $href = $allowed ? e(route($route)) : '#';
        $active = $allowed && request()->routeIs($route) ? ' active' : '';
        $disabled = $allowed ? '' : ' is-disabled';
        $attributes = $allowed ? '' : ' data-unauthorized-menu="'.e($label).'" aria-disabled="true"';
        return '<a href="'.$href.'" class="sidebar-link'.$active.$disabled.'"'.$attributes.'><span class="nav-icon">'.e($icon).'</span><span class="nav-label">'.e($label).'</span></a>';
    };
@endphp

<aside class="site-sidebar" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-brand"><span class="brand-text"></span></div>

    <nav class="sidebar-nav">
        {!! $menuLink('home', 'home', 'Home', '⌂') !!}
        {!! $menuLink('custom-theme', 'theme.custom', 'Custom Tema', '◉') !!}
        {!! $menuLink('daily-report', 'dashboard', 'Daily Report', '▦') !!}
        {!! $menuLink('monthly-report', 'monthly.report', 'Monthly Report', '▤') !!}
        {!! $menuLink('akakami-dashboard', 'akakami.dashboard', 'Akakami Dashboard', '◈') !!}
<div class="sidebar-group {{ $lotVerifikatorOpen ? 'is-open' : '' }} {{ $parentVisible('INPUT LOT') ? '' : 'parent-hidden' }}" data-sidebar-group="input-lot">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $lotVerifikatorOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">INPUT LOT</span></button>
            <div class="sidebar-group-items" {{ $lotVerifikatorOpen || ! $parentVisible('INPUT LOT') ? '' : 'hidden' }}>
                {!! $menuLink('lot-verifikator', 'lot.verifikator', 'Lot Verifikator', '▣') !!}
                {!! $menuLink('gemba-input', 'gemba.input', 'Gemba Input', '⚙') !!}
            </div>
        </div>

          <div class="sidebar-group {{ $pttnOperatorOpen ? 'is-open' : '' }} {{ $parentVisible('PTTN OPERATOR MESIN') ? '' : 'parent-hidden' }}" data-sidebar-group="pttn-operator-mesin">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $pttnOperatorOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">PTTN OPERATOR MESIN</span></button>
            <div class="sidebar-group-items" {{ $pttnOperatorOpen || ! $parentVisible('PTTN OPERATOR MESIN') ? '' : 'hidden' }}>
                {!! $menuLink('pttn-omes-input', 'pttn.omes.input', 'Input PTTN Omes', '⌁') !!}
            </div>
        </div>
        <div class="sidebar-group {{ $approvalOpen ? 'is-open' : '' }} {{ $parentVisible('REPORT APPROVAL') ? '' : 'parent-hidden' }}" data-sidebar-group="approval">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $approvalOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">REPORT APPROVAL</span></button>
            <div class="sidebar-group-items" {{ $approvalOpen || ! $parentVisible('REPORT APPROVAL') ? '' : 'hidden' }}>
                {!! $menuLink('akakami-approval', 'akakami.approval', 'Aprv. Akakami', '✓') !!}
                {!! $menuLink('abnormal-mtc-approval', 'abnormal.mtc.approval', 'Aprv. Abnormal Mtc', '!') !!}
                {!! $menuLink('ranka-approval', 'ranka.approval', 'Aprv. Rank A', '★') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $abnormalOpen ? 'is-open' : '' }} {{ $parentVisible('ABNORMAL REPORT') ? '' : 'parent-hidden' }}" data-sidebar-group="abnormal-report">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $abnormalOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">ABNORMAL REPORT</span></button>
            <div class="sidebar-group-items" {{ $abnormalOpen || ! $parentVisible('ABNORMAL REPORT') ? '' : 'hidden' }}>
                {!! $menuLink('abnormal-report', 'abnormal.report', 'Handling Abn. Report', '!') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $instructionOpen ? 'is-open' : '' }} {{ $parentVisible('LEMBAR Rank A') ? '' : 'parent-hidden' }}" data-sidebar-group="instruction-sheet">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $instructionOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">LEMBAR Rank A</span></button>
            <div class="sidebar-group-items" {{ $instructionOpen || ! $parentVisible('LEMBAR Rank A') ? '' : 'hidden' }}>
                {!! $menuLink('instruction-rank-a', 'instruction.rank.a', 'Rank A', '★') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $manpowerOpen ? 'is-open' : '' }} {{ $parentVisible('MAN POWER') ? '' : 'parent-hidden' }}" data-sidebar-group="manpower">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $manpowerOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">MAN POWER</span></button>
            <div class="sidebar-group-items" {{ $manpowerOpen || ! $parentVisible('MAN POWER') ? '' : 'hidden' }}>
                {!! $menuLink('manpower-control', 'manpower.control', 'Man Power Control', '♟') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $routingOpen ? 'is-open' : '' }} {{ $parentVisible('ROUTING') ? '' : 'parent-hidden' }}" data-sidebar-group="routing">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $routingOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">ROUTING</span></button>
            <div class="sidebar-group-items" {{ $routingOpen || ! $parentVisible('ROUTING') ? '' : 'hidden' }}>
                {!! $menuLink('routing-process', 'routing.process', 'Susun Routing Proses', '⇄') !!}
                {!! $menuLink('routing-pairing-srp', 'routing.pairing.srp', 'Pairing Routing & SRP', '⇆') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $cycleTimeOpen ? 'is-open' : '' }} {{ $parentVisible('CYCLE TIME') ? '' : 'parent-hidden' }}" data-sidebar-group="cycle-time">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $cycleTimeOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">CYCLE TIME</span></button>
            <div class="sidebar-group-items" {{ $cycleTimeOpen || ! $parentVisible('CYCLE TIME') ? '' : 'hidden' }}>
                {!! $menuLink('cycle-time-adjust', 'cycle-time.adjust', 'Adjust Cycle Time', '◴') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $documentProcessOpen ? 'is-open' : '' }} {{ $parentVisible('DOCUMENT PROCESS') ? '' : 'parent-hidden' }}" data-sidebar-group="document-process">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $documentProcessOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">DOCUMENT PROCESS</span></button>
            <div class="sidebar-group-items" {{ $documentProcessOpen || ! $parentVisible('DOCUMENT PROCESS') ? '' : 'hidden' }}>
                {!! $menuLink('document-process-upload', 'document.process.upload', 'Upload WI Proses', '▱') !!}
            </div>
        </div>

        

        <div class="sidebar-group {{ $pttnEngineeringOpen ? 'is-open' : '' }} {{ $parentVisible('PTTN ENGINEERING') ? '' : 'parent-hidden' }}" data-sidebar-group="pttn-engineering">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $pttnEngineeringOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">PTTN ENGINEERING</span></button>
            <div class="sidebar-group-items" {{ $pttnEngineeringOpen || ! $parentVisible('PTTN ENGINEERING') ? '' : 'hidden' }}>
                {!! $menuLink('pttn-add-item-satuan', 'pttn.add-item-satuan', 'Add Item Satuan', '▤') !!}
                {!! $menuLink('pttn-maker', 'pttn.maker', 'PTTN Maker', '▣') !!}
                {!! $menuLink('pttn-pairing-srp', 'pttn.pairing.srp', 'Pairing PTTN to SRP', '⇆') !!}
            </div>
        </div>

      

        <div class="sidebar-group {{ $monitoringOpen ? 'is-open' : '' }} {{ $parentVisible('MONITORING') ? '' : 'parent-hidden' }}" data-sidebar-group="monitoring">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $monitoringOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">MONITORING</span></button>
            <div class="sidebar-group-items" {{ $monitoringOpen || ! $parentVisible('MONITORING') ? '' : 'hidden' }}>
                {!! $menuLink('incoming-trace', 'incoming.trace', 'Incoming Trace', '△') !!}
                {!! $menuLink('realtime-production', 'realtime.production', 'Real Time Prod.', '◷') !!}
                {!! $menuLink('last-sequence', 'last.sequence', 'List Proses Terakhir', '◷') !!}
                {!! $menuLink('manpower-productivity', 'manpower.productivity', 'Perf. Man Power', '♟') !!}
                {!! $menuLink('productivity-process', 'productivity.process', 'Perf. Prod.Process', '⚙') !!}
                {!! $menuLink('carb-temper', 'carb.temper', 'Transisi Carb-Temper', '♨') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $qualityOpen ? 'is-open' : '' }} {{ $parentVisible('QUALITY CONTROL') ? '' : 'parent-hidden' }}" data-sidebar-group="quality-control">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $qualityOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">QUALITY CONTROL</span></button>
            <div class="sidebar-group-items" {{ $qualityOpen || ! $parentVisible('QUALITY CONTROL') ? '' : 'hidden' }}>
                {!! $menuLink('akakami-qaqc', 'akakami.qaqc', 'Proses Akakami QAQC', '▣') !!}
            </div>
        </div>

        <div class="sidebar-group {{ $userManagementOpen ? 'is-open' : '' }} {{ $parentVisible('USER MANAGEMENT') ? '' : 'parent-hidden' }}" data-sidebar-group="user-management">
            <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $userManagementOpen ? 'true' : 'false' }}"><span class="group-chevron">›</span><span class="nav-label">USER MANAGEMENT</span></button>
            <div class="sidebar-group-items" {{ $userManagementOpen || ! $parentVisible('USER MANAGEMENT') ? '' : 'hidden' }}>
                {!! $menuLink('user-management-role', 'user-management.role-akses', 'Role Akses', '♙') !!}
                {!! $menuLink('user-management-control', 'user-management.user-control', 'User Control', '♟') !!}
                {!! $menuLink('user-management-qr', 'user-management.qr-code', 'Buat QR Code User', '▣') !!}
                {!! $menuLink('user-management-authorization', 'user-management.menu-authorization', 'Otorisasi Menu', '☑') !!}
                {!! $menuLink('user-management-hide-menu', 'user-management.hide-menu', 'Hide Menu', '◌') !!}
                {!! $menuLink('user-management-audit', 'user-management.audit-trail', 'Audit Trail', '◔') !!}
                {!! $menuLink('customer-portal-users', 'user-management.customer-portal-users', 'Customer Portal Users', '◎') !!}
            </div>
        </div>
    </nav>
</aside>

<div class="menu-auth-modal" id="unauthorizedMenuModal" hidden role="dialog" aria-modal="true" aria-labelledby="unauthorizedMenuTitle">
    <div class="menu-auth-modal-card">
        <button type="button" class="menu-auth-modal-close" data-close-unauthorized aria-label="Tutup">×</button>
        <div class="menu-auth-modal-icon">!</div>
        <h2 id="unauthorizedMenuTitle">Akses Menu Ditolak</h2>
        <p id="unauthorizedMenuMessage">Anda tidak berwenang Membuka Menu tersebut</p>
        <button type="button" class="btn btn-cyan" data-close-unauthorized>OK</button>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('unauthorizedMenuModal');
        const message = document.getElementById('unauthorizedMenuMessage');
        if (!modal || !message) return;
        const close = () => modal.setAttribute('hidden', '');
        document.addEventListener('click', event => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const unauthorized = target.closest('[data-unauthorized-menu]');
            if (unauthorized) {
                event.preventDefault();
                message.textContent = 'Anda tidak berwenang Membuka Menu tersebut';
                modal.removeAttribute('hidden');
                return;
            }
            if (target.closest('[data-close-unauthorized]') || target === modal) close();
        });
        document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    })();
    @if(session('unauthorized_menu'))
        document.addEventListener('DOMContentLoaded', () => { modal?.removeAttribute('hidden'); });
    @endif
</script>
