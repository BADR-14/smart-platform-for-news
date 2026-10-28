<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MagicLinkVerifyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\Auth\AuthService;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\MagicLinkService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly GoogleAuthService $googleAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly MagicLinkService $magicLinkService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $account = $this->authService->register($request->validated());

        return $this->created(
            ['email' => $account->email],
            'تم إنشاء الحساب. أرسلنا رمز التحقق إلى بريدك الإلكتروني.',
        );
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyEmail(
            $request->validated('email'),
            $request->validated('otp_code'),
            $request->validated('device_name'),
        );

        return $this->ok($this->tokenPayload($result['account'], $result['token']), 'تم تأكيد بريدك الإلكتروني بنجاح.');
    }

    public function resendOtp(EmailRequest $request): JsonResponse
    {
        $this->authService->resendOtp($request->validated('email'));

        return $this->message('إذا كان البريد مسجلاً وغير مؤكد فسيصلك رمز تحقق جديد.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('fcm_token'),
            $request->validated('device_name'),
        );

        return $this->ok($this->tokenPayload($result['account'], $result['token']), 'تم تسجيل الدخول بنجاح.');
    }

    public function googleLogin(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->googleAuthService->loginWithIdToken(
            $request->validated('id_token'),
            $request->validated('device_name'),
        );

        if ($request->filled('fcm_token')) {
            $result['account']->forceFill(['fcm_token' => $request->validated('fcm_token')])->save();
        }

        return $this->ok($this->tokenPayload($result['account'], $result['token']), 'تم تسجيل الدخول بنجاح.');
    }

    // ==================== دخول الأدمن بالرابط السحري ====================

    /**
     * POST /admin/auth/magic-link — يرسل رابط الدخول إلى بريد الأدمن
     */
    public function sendMagicLink(EmailRequest $request): JsonResponse
    {
        $this->magicLinkService->send($request->validated('email'), $request->ip());

        // نفس الرد دائماً: لا نكشف إن كان البريد لحساب أدمن أم لا
        return $this->message('إذا كان البريد لحساب إدارة فسيصلك رابط الدخول خلال لحظات.');
    }

    /**
     * POST /admin/auth/magic-link/verify — يستهلك الرابط ويسجّل الدخول
     */
    public function verifyMagicLink(MagicLinkVerifyRequest $request): JsonResponse
    {
        $result = $this->magicLinkService->consume(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('device_name'),
        );

        return $this->ok($this->tokenPayload($result['account'], $result['token']), 'تم تسجيل الدخول بنجاح.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new AccountResource($request->user()->load(['user', 'company'])));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->message('تم تسجيل الخروج.');
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate(['fcm_token' => ['required', 'string', 'max:255']]);

        $request->user()->forceFill(['fcm_token' => $validated['fcm_token']])->save();

        return $this->message('تم تحديث رمز الإشعارات.');
    }

    // ==================== استعادة كلمة المرور ====================

    /**
     * إرسال رابط إعادة التعيين (لوحة الويب)
     */
    public function sendResetLink(EmailRequest $request): JsonResponse
    {
        $this->passwordResetService->sendLink($request->validated('email'));

        return $this->message('إذا كان البريد مسجلاً فسيصلك رابط لإعادة تعيين كلمة المرور.');
    }

    /**
     * إرسال رمز إعادة التعيين (تطبيق الموبايل)
     */
    public function sendResetCode(EmailRequest $request): JsonResponse
    {
        $this->passwordResetService->sendCode($request->validated('email'));

        return $this->message('إذا كان البريد مسجلاً فسيصلك رمز لإعادة تعيين كلمة المرور.');
    }

    public function verifyResetCode(VerifyOtpRequest $request): JsonResponse
    {
        $this->passwordResetService->assertValidCode($request->validated('email'), $request->validated('otp_code'));

        return $this->message('الرمز صحيح.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['otp_code'])) {
            $this->passwordResetService->resetWithCode($data['email'], $data['otp_code'], $data['password']);
        } else {
            $this->passwordResetService->resetWithToken([
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ]);
        }

        return $this->message('تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
    }

    /**
     * @return array{access_token: string, token_type: string, account: AccountResource}
     */
    private function tokenPayload(Account $account, string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'account' => new AccountResource($account->load(['user', 'company'])),
        ];
    }
}
