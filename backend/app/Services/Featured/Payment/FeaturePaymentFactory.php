<?php

namespace App\Services\Featured\Payment;

use App\Enums\Featured\FeaturePaymentMethod;

class FeaturePaymentFactory
{
    public function make(FeaturePaymentMethod $method): FeaturePaymentStrategy
    {
        return match ($method) {
            FeaturePaymentMethod::Wallet => app(WalletFeaturePayment::class),
            FeaturePaymentMethod::Plan => app(PlanFeaturePayment::class),
        };
    }
}
