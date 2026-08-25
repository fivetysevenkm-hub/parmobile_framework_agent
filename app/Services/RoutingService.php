<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RoutingService
{
    public function routings(): array
    {
        return DB::table('tblrouting')
            ->select(['id_routing', 'nm_routing'])
            ->orderBy('id_routing')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function sequences(): array
    {
        return DB::table('tblsequence')
            ->select(['id_seq', 'nm_sequence'])
            ->orderBy('id_seq')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function detail(string $idRouting): ?array
    {
        $routing = DB::table('tblrouting')->where('id_routing', $idRouting)->first();
        if (! $routing) {
            return null;
        }

        $slots = [];
        for ($index = 1; $index <= 26; $index++) {
            $column = 'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $slots[$index] = $routing->{$column};
        }

        return [
            'id_routing' => (string) $routing->id_routing,
            'nm_routing' => (string) ($routing->nm_routing ?? ''),
            'slots' => $slots,
        ];
    }

    public function save(string $idRouting, string $name, array $slots, bool $isEditMode): void
    {
        $idRouting = trim($idRouting);
        $name = trim($name);
        if ($idRouting === '') {
            throw new RuntimeException('ID Routing wajib diisi.');
        }
        if ($name === '') {
            throw new RuntimeException('Nama Routing wajib diisi.');
        }

        $allowed = DB::table('tblsequence')->pluck('id_seq')->map(fn ($id) => (string) $id)->all();
        $allowedLookup = array_fill_keys($allowed, true);
        $payload = ['nm_routing' => $name];
        for ($index = 1; $index <= 26; $index++) {
            $column = 'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $value = trim((string) ($slots[$index] ?? ''));
            if ($value !== '' && ! isset($allowedLookup[$value])) {
                throw new InvalidArgumentException("Sequence pada slot S{$index} tidak terdaftar.");
            }
            $payload[$column] = $value !== '' ? $value : null;
        }

        DB::transaction(function () use ($idRouting, $payload, $isEditMode): void {
            $exists = DB::table('tblrouting')->where('id_routing', $idRouting)->exists();
            if ($isEditMode) {
                if (! $exists) {
                    throw new RuntimeException('Routing yang akan diperbarui tidak ditemukan.');
                }
                DB::table('tblrouting')->where('id_routing', $idRouting)->update($payload);
                return;
            }

            if ($exists) {
                throw new RuntimeException('ID Routing sudah digunakan. Pilih ID lain atau muat data untuk Update.');
            }
            DB::table('tblrouting')->insert(array_merge(['id_routing' => $idRouting], $payload));
        });
    }
}
