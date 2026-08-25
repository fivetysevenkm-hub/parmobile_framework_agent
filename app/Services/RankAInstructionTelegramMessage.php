<?php

namespace App\Services;

class RankAInstructionTelegramMessage
{
    public function format(array $detail, string $pic, array $approvers = [], $submittedAt = null): string
    {
        $value = static fn ($item): string => trim((string) ($item ?? '-'));
        $tgl = $submittedAt
            ? (is_object($submittedAt) && method_exists($submittedAt, 'format')
                ? $submittedAt->format('Y-m-d H:i:s')
                : (string) $submittedAt)
            : now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $approval = implode("\n", array_map(
            static fn ($approver): string => '• '.$value($approver),
            $approvers,
        ));

        return "🗺️ MOHON APPROVAL - RANK A\n"
            ."---------------------------------\n\n"
            .'No Reg : '.$value($detail['no_rank'] ?? '-')."\n"
            .'Tgl Problem:'.$value($detail['tgl_problem'] ?? $detail['tgl_pengajuan'] ?? $tgl)."\n"
            .'No Lot: '.$value($detail['no_lots'] ?? '-')."\n"
            .'Nama Part: '.$value($detail['nm_part'] ?? '-')."\n"
            .'Qty: '.$value($detail['qty_problem'] ?? '-')."\n"
            .'Deskripsi Masalah: '.$value($detail['deskripsi_problem'] ?? '-')."\n"
            .'Pembuat Instruksi: '.$value($pic)."\n"
            ."Mengajukan Approval ke: \n"
            .$approval."\n"
            ."-----------------------------------\n\n"
            .'Mohon Untuk Di Approved Abnormal Report tersebut, ditunggu segera,'."\n"
            .'LOGIN DENGAN USER ANDA >> PILIH MENU RANK A REPORT>> APPROVAL RANK A' ."\n"
            ."-------------------\n\n";
    }
}
