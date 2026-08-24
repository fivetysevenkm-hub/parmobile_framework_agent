<?php

namespace App\Services;

class AkakamiTelegramMessage
{
    public function format(
        array $detail,
        string $statusLabel,
        string $userName,
        bool $isEscalation = false,
        string $targetApprovers = '',
        bool $isRankA = false,
        string $rankNumber = '',
    ): string {
        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = $isRankA ? '🔴 <b>PENGAJUAN RANK A</b>' : '🟢 <b>PAR MOBILE NOTIFICATION</b>';
        $message = $title."\n━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($isRankA && $rankNumber !== '') {
            $message .= '<b>No. Rank :</b> '.$escape($rankNumber)."\n";
            $message .= '<b>Ref FLPP :</b> '.$escape($detail['no_flpp'] ?? $detail['noflpp'] ?? '-')."\n";
        } else {
            $message .= '<b>No. FLPP :</b> '.$escape($detail['no_flpp'] ?? $detail['noflpp'] ?? '-')."\n";
        }

        $message .= '<b>No. Lot  :</b> '.$escape($detail['no_lots'] ?? '-')."\n";
        $message .= '<b>Part Name:</b> '.$escape($detail['nm_part'] ?? '-')."\n";
        $message .= '<b>Quantity :</b> '.$escape($detail['qty_problem'] ?? '-')."\n";
        $message .= '<b>Customer :</b> '.$escape($detail['nm_customer'] ?? '-')."\n";
        $message .= '<b>Problem  :</b> <i>'.$escape($detail['deskripsi_problem'] ?? $detail['nmproblem'] ?? '-')."</i>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($isRankA) {
            $message .= "📌 <b>STATUS: MENUNGGU TINDAKAN RANK A</b>\n";
        } elseif ($isEscalation) {
            $message .= "⚠️ <b>STATUS: PENGAJUAN APPROVAL</b>\n";
            $message .= '<b>Ditujukan Ke:</b> '.$escape($targetApprovers)."\n";
        } else {
            $message .= '✅ <b>STATUS: '.$escape($statusLabel)."</b>\n";
            $message .= "<b>Keterangan:</b> Lot telah dialirkan &amp; boleh lanjut proses.\n";
        }

        $message .= '<b>Oleh :</b> '.$escape($userName)."\n";
        $message .= '🌐 <i>Link: http://180.250.83.205/</i>';

        return $message;
    }
}
