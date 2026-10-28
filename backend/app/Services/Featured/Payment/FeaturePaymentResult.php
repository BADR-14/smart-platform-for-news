<?php

namespace App\Services\Featured\Payment;

final readonly class FeaturePaymentResult
{
    public function __construct(
        // التكلفة الفعلية للمنشور الواحد (سعر المحفظة، أو حصة المنشور من سعر الباقة)
        public float $cost,
        public ?int $companyPlanId = null,
    ) {}
}
