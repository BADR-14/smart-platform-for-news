<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsVerified
{
    /**
     * النشر متاح للحسابات الموثّقة فقط (مستخدم موثّق أو شركة موافق عليها).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isVerified()) {
            throw new ApiException('يجب توثيق حسابك أولاً حتى تتمكن من النشر.', 403, 'account_not_verified');
        }

        return $next($request);
    }
}
