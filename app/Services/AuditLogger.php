<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public const EVENT_LOGIN = 'login';

    public const EVENT_LOGIN_FAILED = 'login_failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_PASSWORD_CHANGE = 'password_change';

    public const EVENT_ACCESS = 'access';

    public const EVENT_ACCESS_DENIED = 'access_denied';

    public const EVENT_CREATE = 'create';

    public const EVENT_UPDATE = 'update';

    public const EVENT_DELETE = 'delete';

    /**
     * Tulis satu rekaman audit trail.
     */
    public function log(
        string $event,
        string $action,
        ?string $module = null,
        array $oldData = [],
        array $newData = [],
        array $metadata = [],
        ?int $userId = null,
        ?string $username = null,
    ): AuditTrail {
        return AuditTrail::create([
            'user_id' => $userId ?? $this->currentUserId(),
            'username' => $username ?? $this->currentUsername(),
            'event' => $event,
            'module' => $module,
            'action' => $action,
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'old_data' => $this->normalize($oldData),
            'new_data' => $this->normalize($newData),
            'metadata' => $this->normalize($metadata),
            'created_at' => now(),
        ]);
    }

    /**
     * Catat akses ke sebuah menu/route.
     */
    public function access(?string $module, string $routeName): void
    {
        $this->log(self::EVENT_ACCESS, 'Mengakses menu '.($routeName ?: '(tanpa nama)'), $module ?: $routeName);
    }

    public function deniedAccess(?string $module, string $routeName): void
    {
        $this->log(
            self::EVENT_ACCESS_DENIED,
            'Akses menu ditolak: '.($routeName ?: '(tanpa nama)'),
            $module ?: $routeName,
            metadata: ['denied' => true],
        );
    }

    /**
     * Selisih dua state (mis. sebelum/sesudah update) menjadi metadata ringkas.
     */
    public function diff(array $old, array $new): array
    {
        $changes = [];
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
        foreach ($keys as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;
            if ($before != $after) {
                $changes[$key] = [
                    'from' => $before,
                    'to' => $after,
                ];
            }
        }

        return $changes;
    }

    private function currentUserId(): ?int
    {
        $user = auth()->user();
        if ($user) {
            return (int) $user->getAuthIdentifier();
        }

        return (int) (session('id_user') ?: 0) ?: null;
    }

    private function currentUsername(): ?string
    {
        $user = auth()->user();
        if ($user && $user->username) {
            return (string) $user->username;
        }
        $username = session('username');

        return $username ? (string) $username : null;
    }

    private function normalize(array $data): ?array
    {
        return $data === [] ? null : $data;
    }
}
