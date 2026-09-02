<div>
    <div style="max-width:960px;margin:0 auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
            <h1 style="color:var(--ink);font-size:1.3rem;margin:0;font-weight:700;">Customer Portal Users</h1>
            <input type="text" wire:model.live="userSearch" class="control" placeholder="Cari nama, email, perusahaan..." style="width:280px;height:40px;border:1px solid var(--line);border-radius:8px;padding:0 12px;background:var(--panel-deep);color:var(--body-text);font-size:.82rem;">
        </div>

        @if (session('success'))
            <div style="padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.82rem;background:color-mix(in srgb,var(--success) 15%,transparent);border:1px solid color-mix(in srgb,var(--success) 30%,transparent);color:var(--success);">
                {{ session('success') }}
            </div>
        @endif

        @if (empty($users))
            <div style="text-align:center;padding:40px 20px;color:var(--footer-text);font-size:.85rem;">
                Tidak ada data customer portal user.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--line);">
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">Nama</th>
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">Email</th>
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">Perusahaan</th>
                            <th style="text-align:center;padding:10px 12px;color:var(--ink);font-weight:600;">Status</th>
                            <th style="text-align:center;padding:10px 12px;color:var(--ink);font-weight:600;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr style="border-bottom:1px solid var(--line);">
                                <td style="padding:10px 12px;color:var(--body-text);font-weight:500;">{{ $user['name'] }}</td>
                                <td style="padding:10px 12px;color:var(--body-text);">{{ $user['email'] }}</td>
                                <td style="padding:10px 12px;color:var(--body-text);">{{ $user['company'] }}</td>
                                <td style="padding:10px 12px;text-align:center;">
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'suspended' => 'gray',
                                        ];
                                        $color = $statusColors[$user['status']] ?? 'gray';
                                        $labels = [
                                            'pending' => 'Pending',
                                            'approved' => 'Disetujui',
                                            'rejected' => 'Ditolak',
                                            'suspended' => 'Ditangguhkan',
                                        ];
                                    @endphp
                                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600;background:color-mix(in srgb,var(--{{ $color }}) 15%,transparent);color:var(--{{ $color }});">
                                        {{ $labels[$user['status']] ?? $user['status'] }}
                                    </span>
                                </td>
                                <td style="padding:10px 12px;text-align:center;">
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        @if($user['status'] === 'pending')
                                            <button wire:click="approve({{ $user['id'] }})" wire:confirm="Setujui akun {{ $user['email'] }}?" style="padding:4px 12px;border:none;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--success) 15%,transparent);color:var(--success);">
                                                Setujui
                                            </button>
                                            <button wire:click="reject({{ $user['id'] }})" wire:confirm="Tolak akun {{ $user['email'] }}?" style="padding:4px 12px;border:none;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--danger) 15%,transparent);color:var(--danger);">
                                                Tolak
                                            </button>
                                        @elseif($user['status'] === 'approved')
                                            <button wire:click="suspend({{ $user['id'] }})" wire:confirm="Tangguhkan akun {{ $user['email'] }}?" style="padding:4px 12px;border:none;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--warning) 15%,transparent);color:var(--warning);">
                                                Tangguhkan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
