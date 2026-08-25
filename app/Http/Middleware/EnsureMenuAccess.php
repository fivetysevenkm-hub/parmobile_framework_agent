<?php

namespace App\Http\Middleware;

use App\Services\MenuAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = app(MenuAuthorizationService::class);
        $routeName = $request->route()?->getName();
        $menuKey = $authorization->keyForRoute($routeName);

        if ($menuKey === null) {
            return $next($request);
        }

        $user = auth()->user();
        $idUser = $user ? (int) $user->getAuthIdentifier() : (int) (session('id_user') ?: 0);
        $username = $user?->username ?: session('username');

        if ($authorization->canAccess($idUser, (string) $username, $menuKey)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda tidak berwenang Membuka Menu tersebut',
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('unauthorized_menu', 'Anda tidak berwenang Membuka Menu tersebut');
    }
}
