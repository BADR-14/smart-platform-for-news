<?php

namespace App\Services\Company;

use App\Enums\Post\PostStatus;
use App\Models\Company;
use App\Models\Post;
use App\Services\Wallet\WalletService;

class CompanyDashboardService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly CompanyPlanService $companyPlanService,
    ) {}

    /**
     * أرقام الصفحة الرئيسية للوحة الشركة
     *
     * @return array<string, mixed>
     */
    public function getOverview(Company $company): array
    {
        $posts = Post::where('account_id', $company->account_id);

        $byStatus = (clone $posts)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totals = (clone $posts)
            ->selectRaw('COALESCE(SUM(views_count), 0) as views, COALESCE(SUM(likes_count), 0) as likes, COALESCE(SUM(comments_count), 0) as comments, COALESCE(SUM(shares_count), 0) as shares')
            ->first();

        $wallet = $this->walletService->getWallet($company);
        $activePlan = $this->companyPlanService->getActivePlan($company);

        return [
            'posts' => [
                'total' => (int) $byStatus->sum(),
                ...collect(PostStatus::values())->mapWithKeys(
                    fn (string $status) => [$status => (int) ($byStatus[$status] ?? 0)]
                )->all(),
            ],
            'engagement' => [
                'views' => (int) $totals->views,
                'likes' => (int) $totals->likes,
                'comments' => (int) $totals->comments,
                'shares' => (int) $totals->shares,
            ],
            'followers_count' => (int) $company->followers_count,
            'featured_active_count' => $company->featuredPosts()->running()->count(),
            'wallet' => [
                'balance' => (float) $wallet->balance,
                'currency' => $wallet->currency,
            ],
            'active_plan' => $activePlan ? [
                'id' => $activePlan->id,
                'name' => $activePlan->plan?->name,
                'posts_remaining' => $activePlan->posts_remaining,
                'posts_count' => $activePlan->plan?->posts_count,
            ] : null,
        ];
    }
}
