<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Mail\AdminMagicLinkMail;
use App\Models\Account;
use App\Models\AdminLoginLink;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * دخول الأدمن بالرابط السحري (بدون كلمة مرور):
 * يطلب الأدمن رابطاً ببريده، يصله رابط صالح لدقائق ولمرة واحدة، وفتحه يسجّل دخوله.
 *
 * لماذا للأدمن: حساب الأدمن أخطر حساب في المنصة، وكلمة المرور يمكن تخمينها أو تسريبها.
 * الرابط يتطلب الوصول الفعلي لصندوق بريد الأدمن، ولا يوجد سر دائم يُسرق.
 */
class MagicLinkService
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * يرسل الرابط إذا كان البريد لحساب أدمن فعّال. لا يكشف للطالب إن كان البريد صحيحاً أم لا.
     */
    public function send(string $email, ?string $ip = null): void
    {
        $account = Account::where('email', $email)->first();

        if (! $account || ! $account->isAdmin() || ! $account->is_active) {
            return;
        }

        $token = Str::random(64);
        $minutes = (int) config('platform.admin_magic_link.expires_in_minutes', 10);

        DB::transaction(function () use ($account, $token, $minutes, $ip) {
            // رابط واحد صالح في كل وقت: طلب رابط جديد يبطل ما قبله
            AdminLoginLink::where('account_id', $account->id)->whereNull('used_at')->delete();

            AdminLoginLink::create([
                'account_id' => $account->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes($minutes),
                'requested_ip' => $ip,
            ]);
        });

        $url = config('platform.admin_url').'/auth/verify?'.http_build_query([
            'email' => $account->email,
            'token' => $token,
        ]);

        Mail::to($account->email)->send(new AdminMagicLinkMail($url, $minutes));

        // في بيئة التطوير فقط: يُكتب الرابط في السجل لتسهيل التجربة بدون بريد حقيقي
        if (app()->environment('local')) {
            Log::info('Admin magic link', ['url' => $url]);
        }
    }

    /**
     * يتحقق من الرابط ويسجّل الدخول. الرابط يُستهلك حتى لا يُستعمل مرتين.
     *
     * @return array{account: Account, token: string}
     */
    public function consume(string $email, string $token, ?string $deviceName = null): array
    {
        $invalid = new ApiException(
            'رابط الدخول غير صالح أو منتهي الصلاحية. اطلب رابطاً جديداً.',
            422,
            'invalid_magic_link',
        );

        $account = Account::where('email', $email)->first();

        if (! $account || ! $account->isAdmin()) {
            throw $invalid;
        }

        $this->authService->ensureCanSignIn($account);

        $consumed = DB::transaction(function () use ($account, $token) {
            $link = AdminLoginLink::where('account_id', $account->id)
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            return $link ? $link->update(['used_at' => now()]) : false;
        });

        if (! $consumed) {
            throw $invalid;
        }

        $this->notificationService->notifyAuthActivity($account, 'login');

        return [
            'account' => $account,
            'token' => $this->authService->issueToken($account, $deviceName ?: 'admin-dashboard'),
        ];
    }
}
