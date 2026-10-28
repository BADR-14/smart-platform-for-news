<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
                300,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Stripe webhook rejected', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Invalid webhook'], 400);
        }

        $intent = $event->data->object;

        match ($event->type) {
            'payment_intent.succeeded' => $this->walletService->completePayment($intent->id),
            'payment_intent.payment_failed' => $this->walletService->failPayment($intent->id),
            default => null,
        };

        return response()->json(['status' => 'received']);
    }
}
