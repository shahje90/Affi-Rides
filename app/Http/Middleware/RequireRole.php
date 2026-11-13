<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $allowedRole = UserRole::tryFrom($role);

        abort_unless($allowedRole && $request->user()?->role === $allowedRole, 403);

        return $next($request);
    }
}
