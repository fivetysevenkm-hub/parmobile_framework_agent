<?php

namespace App\Services;

class AbnormalMtcTelegramMessage
{
    public function format(array $detail, string $statusLabel, string $userName, int $sequence = 0): string
    {
        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = "🟡 <b>PAR MOBILE APPROVAL - ABNORMAL REPORT</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= '<b>No. ABN    :</b> '.$escape($detail['noabn'] ?? '-')."\n";
        $message .= '<b>Category  :</b> '.$escape($detail['category'] ?? '-')."\n";
        $message .= '<b>Object    :</b> '.$escape($detail['nm_object'] ?? '-')."\n";
        $message .= '<b>Area      :</b> '.$escape($detail['area'] ?? $detail['nm_sequence'] ?? '-')."\n";
        $message .= '<b>Ref Lot   :</b> '.$escape($detail['ref_lot'] ?? '-')."\n";
        $message .= '<b>Problem   :</b> <i>'.$escape($detail['detail_problem'] ?? '-')."</i>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($statusLabel === 'REJECTED') {
            $message .= "❌ <b>STATUS: REJECTED</b>\n";
            $message .= '<b>Oleh   :</b> '.$escape($userName)."\n";
            $message .= "🌐 <i>Segera cek alasan penolakan di sistem.</i>\n";
        } else {
            $message .= "✅ <b>STATUS: APPROVED</b>\n";
            $message .= '<b>Oleh   :</b> '.$escape($userName)."\n";
            $message .= "🌐 <i>Klik link ini untuk approval: http://180.250.83.205/</i>\n";
        }

        return $message;
    }
}
