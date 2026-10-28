<?php

namespace App\Services\Featured\Payment;

use App\Enums\Featured\FeaturePaymentMethod;
use App\Models\Company;
use App\Models\Post;
use App\Services\PlatformSettingsService;
use App\Services\Wallet\WalletService;

/**
 * الدفع من رصيد المحفظة مباشرة بسعر المنشور المفرد الذي يحدده الأدمن
 */
class WalletFeaturePayment implements FeaturePaymentStrategy
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function method(): FeaturePaymentMethod
    {
        return FeaturePaymentMethod::Wallet;
    }

    public function pay(Company $company, Post $post): FeaturePaymentResult
    {
        $price = $this->settings->featuredPostPrice();

        $this->walletService->debit(
            $this->walletService->getWallet($company),
            $price,
            "تمييز المنشور: {$post->title}",
            ['kind' => 'featured_post', 'post_id' => $post->id],
        );

        return new FeaturePaymentResult(cost: $price);
    }
}
