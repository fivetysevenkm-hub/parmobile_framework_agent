<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TelegramAdService
{
    protected const ROTATION_CACHE_KEY = 'telegram_ad_rotation_index';

    public function ads(): array
    {
        return [
            '🎯 Cegah salah proses! Pantau alur tiap lot sekali scan! Verifikator mengarahkan operator langsung ke proses yang benar, tanpa salah langkah. 📲 Yuk coba: https://dowa-par.com',

            '📲 Input Gemba kini semudah swipe! Operator, mesin, tray, dan status proses terekam realtime langsung dari genggaman Anda. Coba sekarang: https://dowa-par.com',

            '📊 Mau lihat posisi produksi hari ini? Pantau status tiap lot secara realtime, lengkap dengan sertifikat SRP elektronik yang siap cetak. Akses di https://dowa-par.com',

            '🔍 Inspeksi PTTN tanpa ribet! Cukup scan QR No Lot, PIC 1 & 2 langsung terdokumentasi. Hemat waktu, akurat, dan aman. Coba di https://dowa-par.com',

            '📈 Rekap produksi harian dalam sekejap! Filter tanggal, grafik performa, sampai detail data produksi lengkap — semua tersaji rapi. Lihat di https://dowa-par.com',

            '🗓️ Tutup bulan dengan data yang berani! Ringkasan performa produksi bulanan per tahun siap membantu evaluasi Anda. Cek di https://dowa-par.com',

            '🔎 Material datang dari mana? Telusuri alur masuk hingga status lot per periode dan proses. Traceability jadi gampang! Buka di https://dowa-par.com',

            '⏱️ Jaga kualitas, cegah crack! Monitor transisi Carb-Temper realtime dengan notifikasi Telegram untuk follow-up lot lebih cepat. Pantau di https://dowa-par.com',

            '🛡️ Kendalikan mutu dari hulu! Catat, setujui, dan rekap temuan Akakami QAQC hingga siap ekspor & cetak. Mulai di https://dowa-par.com',

            '🚨 Ada abnormal? Laporkan, proses, dan eskalasi langsung dari satu layar, plus notifikasi Telegram. Bikin laporan jadi cepat & rapi di https://dowa-par.com',

            '✅ Setujui temuan Akakami tak perlu menunggu giliran! Jejak approval & eskalasi terekam, notifikasi Telegram langsung nyambung. Akses di https://dowa-par.com',

            '👷 Penempatan karyawan jadi terarah! Kelola dan kendalikan data man power di setiap proses operasional dengan rapi. Kelola di https://dowa-par.com',

            '📈 Ukur produktivitas tim, buktikan dengan sertifikat penilaian siap cetak! Analisis man power jadi lebih meyakinkan. Cek di https://dowa-par.com',

            '⚙️ Cycle time, durasi, efisiensi, sampai status scan — semua terukur untuk proses yang makin optimal. Analisis di https://dowa-par.com',

            '👀 Mau lihat posisi terakhir tiap lot sekilas? Daftar proses terakhir langsung jadi bahan monitoring produksi Anda. Lihat di https://dowa-par.com',

            '🧭 Susun urutan proses produksi sesuai kebutuhan SRP dengan rapi dan tepat. Rakit routing Anda di https://dowa-par.com',

            '🎨 Jadikan PARMOBILE milik Anda! Atur warna & tampilan sesuai selera, tersimpan otomatis untuk akun Anda. Gaya di https://dowa-par.com',

            '🪪 Cetak kartu QR NIK siap pakai & cari user aktif beres! Administrasi user jadi serba cepat. Cetak di https://dowa-par.com',

            '🛡️ Tercatat setiap login & aktivitas (IATF 16949). Keamanan data PARMOBILE selalu terjaga & teraudit. Transparan di https://dowa-par.com',

            '🤝 Pelanggan bisa pantau progress lotnya sendiri! Portal khusus nan cepat, informasi produksi dalam genggaman. Masuk di https://dowa-par.com',
        ];
    }

    public function nextFooter(): string
    {
        $ads = $this->ads();
        $index = (int) Cache::increment(self::ROTATION_CACHE_KEY) - 1;
        $ad = $ads[$index % count($ads)];

        return "\n\n───── ✦ ✦ ─────\n".$ad."\n───── ✦ ✦ ─────";
    }
}
