<?php

namespace App\Services\Featured;

use App\Enums\Featured\FeaturedStatus;
use App\Enums\Featured\FeaturePaymentMethod;
use App\Exceptions\ApiException;
use App\Exceptions\PostNotPublishedException;
use App\Exceptions\UnauthorizedException;
use App\Models\Company;
use App\Models\FeaturedPost;
use App\Models\Post;
use App\Services\Featured\Payment\FeaturePaymentFactory;
use App\Services\PlatformSettingsService;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeaturedPostService
{
    public function __construct(
        private readonly FeaturePaymentFactory $paymentFactory,
        private readonly PlatformSettingsService $settings,
        private readonly WalletService $walletService,
    ) {}

    /**
     * ما تحتاجه الواجهة قبل التمييز: السعر، المدة، الرصيد، والباقة النشطة
     *
     * @return array<string, mixed>
     */
    public function getOptions(Company $company): array
    {
        $activePlan = $company->activePlan()->with('plan')->first();

        return [
            'price' => $this->settings->featuredPostPrice(),
            'duration_days' => $this->settings->featuredPostDurationDays(),
            'currency' => config('platform.currency', 'USD'),
            'wallet_balance' => (float) $this->walletService->getWallet($company)->balance,
            'active_plan' => $activePlan ? [
                'id' => $activePlan->id,
                'name' => $activePlan->plan?->name,
                'posts_remaining' => $activePlan->posts_remaining,
            ] : null,
        ];
    }

    /**
     * تمييز منشور للشركة والدفع بالطريقة المختارة
     */
    public function feature(Company $company, Post $post, FeaturePaymentMethod $method): FeaturedPost
    {
        if ((int) $post->account_id !== (int) $company->account_id) {
            throw new UnauthorizedException('يمكنك تمييز منشورات شركتك فقط.');
        }

        if (! $post->isPublished()) {
            throw new PostNotPublishedException('يجب نشر المنشور قبل تمييزه.');
        }

        return DB::transaction(function () use ($company, $post, $method) {
            // قفل المنشور يمنع تمييزه (والدفع عنه) مرتين بطلبين متزامنين
            Post::whereKey($post->id)->lockForUpdate()->first();

            if ($post->featuredPosts()->running()->exists()) {
                throw new ApiException('هذا المنشور مميز حالياً.', 422, 'already_featured');
            }

            $payment = $this->paymentFactory->make($method)->pay($company, $post);

            return FeaturedPost::create([
                'post_id' => $post->id,
                'company_id' => $company->id,
                'cost' => $payment->cost,
                'payment_method' => $method->value,
                'company_plan_id' => $payment->companyPlanId,
                'status' => FeaturedStatus::Active->value,
                'starts_at' => now(),
                'ends_at' => now()->addDays($this->settings->featuredPostDurationDays()),
            ])->load('post');
        });
    }

    public function getCompanyFeaturedPosts(Company $company, int $perPage = 10): LengthAwarePaginator
    {
        return $company->featuredPosts()
            ->with('post')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * كل المنشورات المميزة (للأدمن)
     */
    public function getAllFeaturedPosts(?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return FeaturedPost::query()
            ->with(['post', 'company'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * يحوّل التمييزات المنتهية مدتها إلى expired (يُستدعى من الأمر المجدول featured:expire)
     */
    public function expireDue(): int
    {
        return FeaturedPost::where('status', FeaturedStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => FeaturedStatus::Expired->value]);
    }
}
