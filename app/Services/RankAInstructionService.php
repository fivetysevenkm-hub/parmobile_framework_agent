<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RankAInstructionService
{
    public const STATUSES = ['open', 'eskalasi', 'approved', 'rejected'];

    public function listQuery(string $status, string $search = '', ?string $from = null, ?string $to = null)
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status Rank A tidak valid.');
        }

        $term = '%'.trim($search).'%';
        $sequence = $this->firstLotSubquery();

        return DB::table('tblranka AS r')
            ->leftJoin('tblakakami AS a', 'a.noflpp', '=', 'r.noflpp')
            ->leftJoinSub($sequence, 's', 'a.no_lots', '=', 's.no_lot')
            ->where('r.status', $status)
            ->when(trim($search) !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('r.no_rank', 'like', $term)
                        ->orWhere('r.noflpp', 'like', $term)
                        ->orWhere('a.no_lots', 'like', $term)
                        ->orWhere('s.no_part', 'like', $term)
                        ->orWhere('s.nm_part', 'like', $term)
                        ->orWhere('a.deskripsi_problem', 'like', $term);
                });
            })
            ->when($from !== null && $from !== '', fn ($query) => $query->whereDate('r.tgl_pengajuan', '>=', $from))
            ->when($to !== null && $to !== '', fn ($query) => $query->whereDate('r.tgl_pengajuan', '<=', $to))
            ->select([
                'r.no_rank',
                'r.noflpp',
                'r.tgl_pengajuan',
                'r.instruksi_perbaikan',
                'r.link_ilustrasi',
                's.no_part AS no_brg_asli',
                's.nm_part',
                's.customer AS nm_customer',
                'a.qty_problem',
                'a.deskripsi_problem',
                'r.status',
                'a.no_lots',
            ])
            ->orderByDesc('r.tgl_pengajuan')
            ->orderByDesc('r.no_rank');
    }

    public function detail(string $noRank): ?array
    {
        $rank = DB::table('tblranka AS r')
            ->leftJoin('tblakakami AS a', 'a.noflpp', '=', 'r.noflpp')
            ->leftJoinSub($this->firstLotSubquery(), 's', 'a.no_lots', '=', 's.no_lot')
            ->where('r.no_rank', $noRank)
            ->first([
                'r.no_rank',
                'r.noflpp',
                'r.tgl_pengajuan',
                'r.instruksi_perbaikan',
                'r.link_ilustrasi',
                's.no_part AS no_brg_asli',
                's.nm_part',
                's.customer AS nm_customer',
                'a.qty_problem',
                'a.deskripsi_problem',
                'r.status',
                'a.no_lots',
            ]);

        return $rank ? (array) $rank : null;
    }

    private function firstLotSubquery()
    {
        $firstIds = DB::table('tbl_seq_all')
            ->selectRaw('MIN(id)')
            ->groupBy('no_lot');

        return DB::table('tbl_seq_all AS ss')
            ->whereIn('ss.id', $firstIds)
            ->select(['ss.no_lot', 'ss.no_part', 'ss.nm_part', 'ss.customer']);
    }

    public function approvers(): array
    {
        return DB::table('vw_pic_approve')
            ->select(['nik', 'user', 'jabatan', 'class'])
            ->orderBy('user')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function approvalProgress(string $noRank): array
    {
        return DB::table('tbl_approval_ranka AS ar')
            ->leftJoin('tbl_user AS u', 'ar.nik_approver', '=', 'u.id_user')
            ->where('ar.id_ranka', $noRank)
            ->orderByRaw('CAST(ar.seq_no AS UNSIGNED)')
            ->orderBy('ar.id_approval')
            ->get([
                'ar.seq_no',
                'ar.nik_approver',
                'ar.role_approver',
                'ar.approve_status',
                'ar.tgl_approve',
                'ar.remark',
                'u.username AS nama',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function updateAndEscalate(array $data, array $approverNiks): array
    {
        if ($approverNiks === []) {
            throw new RuntimeException('Minimal satu PIC Judgment harus dipilih.');
        }

        return DB::transaction(function () use ($data, $approverNiks): array {
            $rank = DB::table('tblranka')->where('no_rank', $data['no_rank'])->lockForUpdate()->first();

            if (! $rank) {
                throw new RuntimeException('Data Rank A tidak ditemukan.');
            }

            if (! in_array((string) $rank->status, ['open', 'rejected'], true)) {
                throw new RuntimeException('Rank A hanya dapat diproses dari status OPEN atau REJECTED.');
            }

            DB::table('tblranka')->where('no_rank', $data['no_rank'])->update([
                'instruksi_perbaikan' => $data['instruksi_perbaikan'],
                'link_ilustrasi' => $data['link_ilustrasi'],
                'status' => 'eskalasi',
            ]);

            DB::table('tbl_approval_ranka')->where('id_ranka', $data['no_rank'])->delete();

            $selected = array_values(array_unique(array_map('strval', $approverNiks)));
            $approverRows = DB::table('vw_pic_approve')
                ->whereIn('nik', $selected)
                ->orderByDesc('class')
                ->orderBy('user')
                ->get(['nik', 'user', 'class']);

            if ($approverRows->isEmpty()) {
                throw new RuntimeException('PIC approver yang dipilih tidak ditemukan.');
            }

            $now = now('Asia/Jakarta');
            $names = [];
            foreach ($approverRows as $index => $approver) {
                $sequence = $index + 1;
                $names[] = (string) $approver->user;
                DB::table('tbl_approval_ranka')->insert([
                    'id_ranka' => $data['no_rank'],
                    'seq_no' => (string) $sequence,
                    'nik_approver' => (string) $approver->nik,
                    'role_approver' => (string) ($approver->class ?? ''),
                    'approve_status' => $sequence === 1 ? 'waiting' : 'pending',
                    'tgl_approve' => $now,
                ]);
            }

            return [
                'detail' => $this->detail($data['no_rank']),
                'approver_names' => $names,
                'submitted_at' => $now,
            ];
        });
    }
}
