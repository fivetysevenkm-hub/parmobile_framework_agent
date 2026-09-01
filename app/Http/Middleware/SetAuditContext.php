<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetAuditContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DB::getDriverName() === 'mysql') {
            $user = auth()->user();
            $username = $user?->username ?: session('username');
            $idUser = $user ? (int) $user->getAuthIdentifier() : (int) (session('id_user') ?: 0);

            $safeUsername = $username ? str_replace("'", "''", (string) $username) : 'NULL';

            try {
                DB::statement('SET @audit_user = '.$safeUsername);
                DB::statement('SET @audit_user_id = '.($idUser ?: 'NULL'));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $next($request);
    }
}
