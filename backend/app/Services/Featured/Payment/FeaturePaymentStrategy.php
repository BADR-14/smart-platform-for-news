<?php

namespace App\Services\Featured\Payment;

use App\Enums\Featured\FeaturePaymentMethod;
use App\Models\Company;
use App\Models\Post;

/**
 * Strategy: طريقة دفع تكلفة تمييز منشور.
 * إضافة طريقة دفع جديدة = صنف جديد يطبّق هذه الواجهة دون تعديل FeaturedPostService.
 */
interface FeaturePaymentStrategy
{
    public function method(): FeaturePaymentMethod;

    /**
     * ينفّذ الدفع ويرجع ما تم خصمه. يرمي ApiException إذا تعذّر الدفع.
     * يُستدعى دائماً داخل Transaction.
     */
    public function pay(Company $company, Post $post): FeaturePaymentResult;
}
