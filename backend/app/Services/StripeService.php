<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    private ?StripeClient $client = null;

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function createPaymentIntent(float $amount, string $currency, array $meta = []): PaymentIntent
    {
        return $this->client()->paymentIntents->create([
            // Stripe يتعامل بأصغر وحدة للعملة (سنت)
            'amount' => (int) round($amount * 100),
            'currency' => strtolower($currency),
            'metadata' => $meta,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
        ]);
    }

    public function retrievePaymentIntent(string $intentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($intentId, ['expand' => ['latest_charge']]);
    }

    /**
     * الاتصال يُنشأ عند الحاجة فقط حتى لا يفشل باقي التطبيق إذا لم تُضبط مفاتيح Stripe
     */
    private function client(): StripeClient
    {
        if (! $this->isConfigured()) {
            throw new ApiException('بوابة الدفع غير مهيّأة حالياً.', 503, 'payment_gateway_unavailable');
        }

        return $this->client ??= new StripeClient(config('services.stripe.secret'));
    }
}
