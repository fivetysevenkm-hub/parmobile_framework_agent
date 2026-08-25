<?php

namespace App\Services;

class AbnormalReportTelegramMessage
{
    public function format(array $detail, string $userName, array $approvers = [], ?string $submittedAt = null): string
    {
        $value = static fn ($item): string => trim((string) ($item ?? ''));
        $date = $submittedAt !== null && $submittedAt !== ''
            ? $submittedAt
            : now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $approverText = implode("\n", array_map(
            static fn ($approver): string => $value($approver),
            $approvers,
        ));

        return "🗺️ MOHON APPROVAL - ABN REPORT\n"
            ."---------------------------------\n\n"
            .'No Abn. Report: '.$value($detail['noabn'] ?? '-')."\n"
            .'Tgl: '.$date."\n"
            .'Area Problem: '.$value($detail['nm_sequence'] ?? $detail['area'] ?? '-')."\n"
            .'Kategori: '.$value($detail['category'] ?? '-')."\n"
            .'Nama Object: '.$value($detail['nm_object'] ?? '-')."\n"
            .'Detail Problem: '.$value($detail['detail_problem'] ?? '-')."\n"
            .'Penemu Problem: '.$value($detail['nama_kary'] ?? $detail['found_by'] ?? '-')."\n"
            .'PIC: '.$value($userName)."\n"
            ."Mengajukan Approval ke: \n"
            .$approverText."\n"
            ."-----------------------------------\n\n"
            .'Mohon Untuk Di Approved Abnormal Report tersebut, ditunggu segera,'."\n"
            ."-------------------\n\n";
    }
}
