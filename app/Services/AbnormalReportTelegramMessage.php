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

    public function formatGemba(array $detail, ?string $reportedAt = null): string
    {
        $value = static fn ($item): string => htmlspecialchars(trim((string) ($item ?? '-')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $date = $reportedAt !== null && $reportedAt !== ''
            ? $reportedAt
            : now('Asia/Jakarta')->format('Y-m-d H:i:s');

        return "📌 NOTIFIKASI ABNORMAL MTC/PROD\n"
            ."-----------------------------------\n\n"
            .'No Abn. Report: '.$value($detail['noabn'] ?? '-')."\n"
            .'Tgl: '.$date."\n"
            .'Area Problem: '.$value($detail['nm_sequence'] ?? $detail['area'] ?? '-')."\n"
            .'Kategori: '.$value($detail['category'] ?? '-')."\n"
            .'Nama Object: '.$value($detail['nm_object'] ?? '-')."\n"
            .'Detail Problem: '.$value($detail['detail_problem'] ?? '-')."\n"
            .'Pelapor: '.$value($detail['found_by_name'] ?? $detail['nama_kary'] ?? $detail['found_by'] ?? '-')."\n"
            ."-----------------------------------\n\n"
            ."⚠️ Mohon Team Maintenance & prod Dept untuk segera memverifikasi dan menindaklanjuti, Lakukan Genba ke Lapangan. Terima kasih atas perhatiannya.\n"
            ."Jika Ditemukan Abnormal, Lakukan STOP-CALL-WAIT.\n"
            ."Pastikan data Laporan Abnormal Trouble Report pada system PAR dilengkapi.\n"
            ."-------------------\n\n";
    }
}
