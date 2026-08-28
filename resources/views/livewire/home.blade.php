<div class="home-page" style="font-family:Tahoma,Arial,sans-serif;color:var(--legacy_f4f7f8);">
    <style>
        .home-page{width:100%;max-width:1180px;margin:0 auto;padding:8px 4px 28px;box-sizing:border-box;overflow-x:hidden}
        .home-hero{background:linear-gradient(135deg,var(--legacy_112c36),var(--legacy_123f4b) 58%,var(--legacy_0b2028);border:1px solid var(--legacy_1d5b68);border-radius:12px;padding:22px 24px;box-shadow:0 10px 24px rgba(0,0,0,.22);margin-bottom:16px}
        .home-kicker{font-size:12px;letter-spacing:1.5px;color:var(--text-header);font-weight:700;text-transform:uppercase;margin-bottom:9px}
        .home-title{font-size:25px;line-height:1.2;margin:0 0 8px;color:var(--text-title);font-weight:700}
        .home-subtitle{font-size:14px;line-height:1.6;color:var(--text-body);margin:0;max-width:780px}
        .home-panel{background:var(--legacy_101c20);border:1px solid var(--legacy_263b40);border-radius:10px;padding:16px;box-shadow:0 7px 18px rgba(0,0,0,.16)}
        .home-panel-title{font-size:17px;color:var(--text-title);margin:0 0 4px;font-weight:700}
        .home-panel-intro{font-size:13px;color:var(--text-body);margin:0 0 13px;line-height:1.5}
        .home-table-wrap{width:100%;max-width:100%;overflow-x:auto;border:1px solid var(--legacy_30464b);border-radius:8px;box-sizing:border-box}
        .home-table{width:100%;min-width:0;border-collapse:collapse;font-size:13px;table-layout:fixed}
        .home-table th{background:var(--legacy_183b48);color:var(--text-table);text-align:left;font-size:12px;letter-spacing:.25px;padding:11px 12px;border-bottom:1px solid var(--legacy_3e6973);white-space:normal;overflow-wrap:anywhere}
        .home-table td{padding:10px 12px;vertical-align:top;border-bottom:1px solid var(--legacy_26383d);color:var(--text-table);line-height:1.45;white-space:normal!important;overflow-wrap:anywhere;word-break:break-word;text-align:left!important}
        .home-table tr:last-child td{border-bottom:0}
        .home-table tr:nth-child(even) td{background:var(--legacy_142126)}
        .home-table tr:hover td{background:var(--legacy_1b3036)}.home-table th:last-child{width:55%}
        .home-menu-name{font-weight:700;color:var(--text-title);white-space:normal;overflow-wrap:anywhere}
        .home-group{display:inline-block;font-size:11px;color:var(--text-table);background:var(--legacy_164552);border:1px solid var(--legacy_276a76);border-radius:12px;padding:3px 8px;margin-bottom:5px;white-space:nowrap}
        .home-function{width:55%;max-width:55%;color:var(--text-table);white-space:normal!important;overflow-wrap:anywhere;word-break:normal;text-align:left!important}
        .home-open{display:inline-block;color:var(--text-header);text-decoration:none;font-size:12px;margin-top:4px}
        .home-open:hover{text-decoration:underline}
        .home-note{margin-top:16px;border-left:4px solid var(--legacy_e0ad55);background:var(--legacy_2a2418);border-radius:7px;padding:12px 14px;color:var(--text-footer);font-size:13px;line-height:1.55}
        .home-note strong{color:var(--text-title)}
        @media(max-width:650px){.home-page{width:100%;padding:4px 0 22px}.home-hero{padding:17px 14px;border-radius:9px}.home-kicker{font-size:11px;letter-spacing:1px}.home-title{font-size:20px;line-height:1.25;overflow-wrap:anywhere}.home-subtitle{font-size:13px;line-height:1.5}.home-panel{padding:11px 10px;border-radius:9px}.home-panel-title{font-size:16px}.home-panel-intro{font-size:12px;line-height:1.45}.home-table-wrap{overflow:visible;border:0}.home-table,.home-table tbody{display:block;width:100%;max-width:100%;min-width:0;font-size:13px;box-sizing:border-box}.home-table thead{display:none}.home-table tr{display:block;width:100%;max-width:100%;min-width:0;margin:0 0 10px;padding:8px 10px;background:var(--legacy_142126);border:1px solid var(--legacy_30464b);border-radius:8px;box-sizing:border-box;overflow:hidden}.home-table tr:nth-child(even) td,.home-table tr:hover td{background:transparent}.home-table td,.home-table td.home-function{display:block!important;width:100%;max-width:100%;min-width:0;box-sizing:border-box;padding:7px 0;border:0;line-height:1.4;white-space:normal!important;overflow-wrap:anywhere;word-break:break-word;overflow:hidden}.home-table td::before{display:block;margin-bottom:3px;color:var(--legacy_83aeb5);font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;white-space:normal}.home-table td[data-label="Menu"]::before{content:'Menu'}.home-table td[data-label="Kelompok"]::before{content:'Kelompok'}.home-table td[data-label="Fungsi Menu"]::before{content:'Fungsi Menu'}.home-table td[data-label="Fungsi Menu"]{padding-top:8px}.home-group{font-size:10px;padding:3px 7px;white-space:normal}.home-open{font-size:12px}.home-note{margin-top:12px;padding:11px 12px;font-size:12px;line-height:1.5}}
    </style>

    <section class="home-hero">
        <div class="home-kicker">PAR Mobile</div>
        <h1 class="home-title">Selamat datang, {{ $username }}</h1>
        <p class="home-subtitle">Anda berhasil login ke aplikasi PAR Mobile. Berikut adalah daftar menu yang dapat Anda akses berdasarkan otorisasi akun Anda.</p>
    </section>

    <section class="home-panel">
        <h2 class="home-panel-title">Menu yang dapat diakses</h2>
        <p class="home-panel-intro">Gunakan tabel berikut sebagai panduan fungsi setiap menu. Daftar menyesuaikan hak akses dan pengaturan Hide Menu user yang sedang login.</p>

        <div class="home-table-wrap">
            <table class="home-table">
                <thead>
                    <tr>
                        <th style="width:23%;">Menu</th>
                        <th style="width:22%;">Kelompok</th>
                        <th>Fungsi Menu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accessibleMenus as $menu)
                        <tr>
                            <td data-label="Menu">
                                <div class="home-menu-name">{{ $menu['name'] }}</div>
                                @if($menu['route'] !== '')
                                    <a class="home-open" href="{{ route($menu['route']) }}">Buka menu &rarr;</a>
                                @endif
                            </td>
                            <td data-label="Kelompok"><span class="home-group">{{ $menu['group'] }}</span></td>
                            <td data-label="Fungsi Menu" class="home-function">{{ $menu['description'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:var(--legacy_f0b2b2);padding:18px;">Belum ada menu yang diotorisasi untuk akun ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="home-note">
            <strong>Himbauan penggunaan informasi:</strong> gunakan informasi pada PAR Mobile secara bijak, sesuai kewenangan dan kebutuhan pekerjaan. Data produksi, kualitas, user, dan laporan merupakan informasi operasional yang perlu dijaga kerahasiaannya. Jangan membagikan data kepada pihak yang tidak berwenang dan selalu lakukan logout setelah selesai menggunakan aplikasi.
        </div>
    </section>
</div>
