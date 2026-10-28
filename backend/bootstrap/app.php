<?php

use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\OptionalAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // الـ API يعتمد على Bearer Token فقط (بدون جلسات أو كوكيز)
        $middleware->api(prepend: [ForceJsonResponse::class]);
        $middleware->throttleApi();
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'admin' => CheckAdmin::class,
            'role' => RoleMiddleware::class,
            'verified.account' => EnsureAccountIsVerified::class,
            'auth.optional' => OptionalAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ردود أخطاء موحّدة وبالعربية لكل مسارات الـ API
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول أولاً.',
                'code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $isMissingModel = $e->getPrevious() instanceof ModelNotFoundException;

            return response()->json([
                'message' => $isMissingModel ? 'العنصر المطلوب غير موجود.' : 'المسار المطلوب غير موجود.',
                'code' => 'not_found',
            ], 404);
        });

        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e, Request $request) {
            return response()->json([
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء.',
                'code' => 'forbidden',
            ], 403);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json([
                'message' => 'عدد المحاولات كبير. الرجاء الانتظار قليلاً ثم المحاولة مجدداً.',
                'code' => 'too_many_requests',
            ], 429, $e->getHeaders());
        });
    })->create();
