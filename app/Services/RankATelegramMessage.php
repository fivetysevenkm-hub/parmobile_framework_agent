<?php

namespace App\Services;

class RankATelegramMessage
{
    public function format(
        array $detail,
        string $statusLabel,
        string $userName,
        int $sequence,
        ?int $nextSequence = null,
    ): string {
        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = "🔵 <b>PAR MOBILE APPROVAL-RANK A</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= '<b>No. Rank  :</b> '.$escape($detail['no_rank'] ?? '-')."\n";
        $message .= '<b>No. FLPP  :</b> '.$escape($detail['noflpp'] ?? '-')."\n";
        $message .= '<b>Tgl Pengajuan :</b> '.$escape($detail['tgl_pengajuan'] ?? '-')."\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($statusLabel === 'APPROVED (PARTIAL)') {
            $message .= "⏳ <b>STATUS: APPROVED (PARTIAL)</b>\n";
            $message .= '<b>Oleh  :</b> '.$escape($userName).' (Seq '.$sequence.")\n";
            $message .= '<b>Next  :</b> Menunggu Sequence '.($nextSequence ?? ($sequence + 1))."\n";
            $message .= "🌐 <i>Klik link ini untuk approval: http://180.250.83.205/</i>\n";
        } elseif ($statusLabel === 'FULLY APPROVED') {
            $message .= "✅ <b>STATUS: FULLY APPROVED</b>\n";
            $message .= '<b>Oleh  :</b> '.$escape($userName)." (Final Approver)\n";
            $message .= "🌐 <i>Klik link ini untuk approval: http://180.250.83.205/</i>\n";
        } else {
            $message .= "❌ <b>STATUS: REJECTED</b>\n";
            $message .= '<b>Oleh  :</b> '.$escape($userName)."\n";
            $message .= "⚠️ <i>Dokumen dibatalkan secara permanen.</i>";
        }

        return $message;
    }
}
