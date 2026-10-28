<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * للمسارات العامة التي يتصفحها الزائر: إذا أُرسل توكن صالح نتعرّف على صاحبه
 * (لإرجاع is_bookmarked / my_reaction ...) وإذا لم يُرسل يكمل الطلب كزائر.
 */
class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            Auth::shouldUse('sanctum');
        }

        return $next($request);
    }
}
