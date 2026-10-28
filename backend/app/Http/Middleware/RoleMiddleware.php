<?php

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * الاستخدام: role:Company أو role:Company,Admin
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role()->label();

        if (! $role || ! in_array($role, $roles, true)) {
            throw new UnauthorizedException('نوع حسابك لا يملك صلاحية الوصول لهذا القسم.');
        }

        return $next($request);
    }
}
