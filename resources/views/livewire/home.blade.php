<div class="home-page" style="font-family:Tahoma,Arial,sans-serif;color:#f4f7f8;">
    <style>
        .home-page{max-width:1180px;margin:0 auto;padding:8px 4px 28px}
        .home-hero{background:linear-gradient(135deg,#112c36,#123f4b 58%,#0b2028);border:1px solid #1d5b68;border-radius:12px;padding:22px 24px;box-shadow:0 10px 24px rgba(0,0,0,.22);margin-bottom:16px}
        .home-kicker{font-size:12px;letter-spacing:1.5px;color:#71d9e6;font-weight:700;text-transform:uppercase;margin-bottom:9px}
        .home-title{font-size:25px;line-height:1.2;margin:0 0 8px;color:#fff;font-weight:700}
        .home-subtitle{font-size:14px;line-height:1.6;color:#d2e3e7;margin:0;max-width:780px}
        .home-panel{background:#101c20;border:1px solid #263b40;border-radius:10px;padding:16px;box-shadow:0 7px 18px rgba(0,0,0,.16)}
        .home-panel-title{font-size:17px;color:#fff;margin:0 0 4px;font-weight:700}
        .home-panel-intro{font-size:13px;color:#aebfc3;margin:0 0 13px;line-height:1.5}
        .home-table-wrap{overflow-x:auto;border:1px solid #30464b;border-radius:8px}
        .home-table{width:100%;min-width:700px;border-collapse:collapse;font-size:13px}
        .home-table th{background:#183b48;color:#e9fcff;text-align:left;font-size:12px;letter-spacing:.25px;padding:11px 12px;border-bottom:1px solid #3e6973;white-space:nowrap}
        .home-table td{padding:10px 12px;vertical-align:top;border-bottom:1px solid #26383d;color:#e6eef0;line-height:1.45}
        .home-table tr:last-child td{border-bottom:0}
        .home-table tr:nth-child(even) td{background:#142126}
        .home-table tr:hover td{background:#1b3036}
        .home-menu-name{font-weight:700;color:#fff;white-space:nowrap}
        .home-group{display:inline-block;font-size:11px;color:#8de3ec;background:#164552;border:1px solid #276a76;border-radius:12px;padding:3px 8px;margin-bottom:5px;white-space:nowrap}
        .home-function{color:#d0dddf}
        .home-open{display:inline-block;color:#69dce8;text-decoration:none;font-size:12px;margin-top:4px}
        .home-open:hover{text-decoration:underline}
        .home-note{margin-top:16px;border-left:4px solid #e0ad55;background:#2a2418;border-radius:7px;padding:12px 14px;color:#f4dfb4;font-size:13px;line-height:1.55}
        .home-note strong{color:#ffe7ad}
        @media(max-width:650px){.home-page{padding:4px 0 22px}.home-hero{padding:18px 16px}.home-title{font-size:21px}.home-subtitle{font-size:13px}.home-panel{padding:12px}.home-panel-title{font-size:16px}.home-table{font-size:12px}.home-table th,.home-table td{padding:9px 10px}}
    </style>

    <section class="home-hero">
        <div class="home-kicker">PAR Mobile</div>
        <h1 class="home-title">Selamat datang, {{ $username }}</h1>
        <p class="home-subtitle">Anda berhasil login ke aplikasi PAR Mobile. Berikut adalah daftar menu yang dapat Anda akses berdasarkan otorisasi akun Anda.</p>
    </section>

    <section class="home-panel">
        <h2 class="home-panel-title">Menu yang dapat diakses</h2>
        <p class="home-panel-intro">Gunakan tabel berikut sebagai panduan fungsi setiap menu. Daftar akan menyesuaikan hak akses user yang sedang login.</p>

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
                            <td>
                                <div class="home-menu-name">{{ $menu['name'] }}</div>
                                @if($menu['route'] !== '')
                                    <a class="home-open" href="{{ route($menu['route']) }}">Buka menu &rarr;</a>
                                @endif
                            </td>
                            <td><span class="home-group">{{ $menu['group'] }}</span></td>
                            <td class="home-function">{{ $menu['description'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:#f0b2b2;padding:18px;">Belum ada menu yang diotorisasi untuk akun ini.</td>
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
