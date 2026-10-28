<?php

namespace App\Providers;

use App\Contracts\DocumentTextExtractor;
use App\Models\Account;
use App\Models\Company;
use App\Models\Post;
use App\Observers\CompanyObserver;
use App\Observers\PostObserver;
use App\Services\External\GoogleVisionService;
use App\Services\PlatformSettingsService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // قارئ الوثائق خلف واجهة حتى يمكن تبديله أو تزييفه في الاختبارات
        $this->app->bind(DocumentTextExtractor::class, GoogleVisionService::class);

        // نسخة واحدة لكل طلب حتى تُقرأ الإعدادات من قاعدة البيانات مرة واحدة فقط
        $this->app->scoped(PlatformSettingsService::class);
    }

    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Company::observe(CompanyObserver::class);

        // الأنواع المخزّنة في reports.reportable_type
        Relation::morphMap([
            'post' => Post::class,
            'account' => Account::class,
        ]);

        $this->configureRateLimiting();
        $this->configurePasswords();
    }

    private function configureRateLimiting(): void
    {
        $key = fn (Request $request) => strtolower((string) $request->input('email')).'|'.$request->ip();

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        // الدخول والتسجيل: 10 محاولات بالدقيقة لكل (بريد + IP)
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($key($request)));

        // رموز OTP من 6 أرقام: حد صارم يمنع التخمين
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(5)->by($key($request)),
            Limit::perHour(30)->by($key($request)),
        ]);
    }

    private function configurePasswords(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        ResetPassword::createUrlUsing(fn (Account $account, string $token) => config('platform.frontend_url')
            .'/reset-password?token='.$token.'&email='.urlencode($account->email));
    }
}
