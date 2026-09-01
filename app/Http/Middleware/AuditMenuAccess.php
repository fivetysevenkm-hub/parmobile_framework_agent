<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Services\MenuAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return $next($request);
        }

        $authorization = app(MenuAuthorizationService::class);
        $menuKey = $authorization->keyForRoute($routeName);

        if ($menuKey === null) {
            return $next($request);
        }

        $user = auth()->user();
        $idUser = $user ? (int) $user->getAuthIdentifier() : (int) (session('id_user') ?: 0);
        $username = $user?->username ?: session('username');
        $allowed = $authorization->canAccess($idUser, (string) $username, $menuKey);

        $logger = app(AuditLogger::class);
        if ($allowed) {
            $logger->access($menuKey, $routeName);
        } else {
            $logger->deniedAccess($menuKey, $routeName);
        }

        return $next($request);
    }
}
