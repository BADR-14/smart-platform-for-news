<?php

namespace App\Services\Wallet;

use App\Enums\Wallet\TransactionType;
use App\Exceptions\ApiException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\StripeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private readonly StripeService $stripeService) {}

    // ==================== Queries ====================

    public function getWallet(Company $company): Wallet
    {
        // الـ CompanyObserver ينشئ المحفظة مع الشركة؛ firstOrCreate يغطي الشركات القديمة
        return $company->wallet()->firstOrCreate([], [
            'name' => 'Main Wallet',
            'description' => 'المحفظة الرئيسية للشركة',
            'balance' => 0,
            'currency' => config('platform.currency', 'USD'),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{total_credited: float, total_debited: float}
     */
    public function getSummary(Wallet $wallet): array
    {
        $totals = $wallet->transactions()->reorder()
            ->where('status', 'completed')
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'total_credited' => round((float) ($totals['credit'] ?? 0) + (float) ($totals['gift'] ?? 0), 2),
            'total_debited' => round((float) ($totals['debit'] ?? 0), 2),
        ];
    }

    public function getTransactions(Company $company, ?string $type, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getWallet($company)
            ->transactions()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->paginate($perPage);
    }

    // ==================== Top-up (Stripe) ====================

    /**
     * الخطوة 1: إنشاء PaymentIntent وإرجاع client_secret للواجهة لتكمل الدفع عبر Stripe.js
     *
     * @return array<string, mixed>
     */
    public function initiateTopUp(Company $company, float $amount): array
    {
        $wallet = $this->getWallet($company);
        $this->ensureActive($wallet);

        $intent = $this->stripeService->createPaymentIntent(
            amount: $amount,
            currency: $wallet->currency,
            meta: ['company_id' => $company->id, 'wallet_id' => $wallet->id],
        );

        Payment::create([
            'company_id' => $company->id,
            'gateway_name' => 'stripe',
            'gateway_payment_id' => $intent->id,
            'amount' => $amount,
            'currency' => $wallet->currency,
            'status' => 'pending',
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'publishable_key' => $this->stripeService->publishableKey(),
            'amount' => $amount,
            'currency' => $wallet->currency,
        ];
    }

    /**
     * الخطوة 2 (بديل الـ Webhook للتشغيل المحلي): الواجهة تطلب التأكيد بعد نجاح الدفع،
     * فنسأل Stripe مباشرة عن حالة العملية ولا نثق بما ترسله الواجهة.
     */
    public function confirmTopUp(Company $company, string $paymentIntentId): Wallet
    {
        $payment = Payment::where('company_id', $company->id)
            ->where('gateway_payment_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            throw new ApiException('عملية الدفع غير موجودة.', 404, 'not_found');
        }

        $intent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            throw new ApiException('لم تكتمل عملية الدفع بعد.', 422, 'payment_not_completed');
        }

        $this->completePayment($paymentIntentId, $intent->latest_charge->receipt_url ?? null);

        return $this->getWallet($company)->refresh();
    }

    /**
     * يُستدعى من الـ Webhook ومن confirmTopUp. آمن للتكرار: الرصيد يُضاف مرة واحدة فقط
     * مهما تكرر الاستدعاء لنفس العملية.
     */
    public function completePayment(string $paymentIntentId, ?string $receiptUrl = null): void
    {
        DB::transaction(function () use ($paymentIntentId, $receiptUrl) {
            $payment = Payment::where('gateway_payment_id', $paymentIntentId)->lockForUpdate()->first();

            if (! $payment || ! $payment->isPending()) {
                return;
            }

            $payment->update(['status' => 'completed', 'receipt_url' => $receiptUrl]);

            $this->credit(
                wallet: $this->getWallet($payment->company),
                amount: (float) $payment->amount,
                description: 'شحن المحفظة عبر Stripe',
                paymentId: $payment->id,
                meta: ['stripe_intent_id' => $paymentIntentId],
            );
        });
    }

    public function failPayment(string $paymentIntentId): void
    {
        Payment::where('gateway_payment_id', $paymentIntentId)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);
    }

    // ==================== Balance operations ====================

    /**
     * @param  array<string, mixed>  $meta
     */
    public function credit(
        Wallet $wallet,
        float $amount,
        string $description,
        TransactionType $type = TransactionType::Credit,
        ?int $paymentId = null,
        array $meta = [],
    ): Transaction {
        return DB::transaction(function () use ($wallet, $amount, $description, $type, $paymentId, $meta) {
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);
            $wallet->increment('balance', $amount);

            return $this->record($wallet, $type, $amount, $description, $paymentId, $meta);
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function debit(Wallet $wallet, float $amount, string $description, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $meta) {
            // قفل السجل يمنع خصمين متزامنين من تجاوز الرصيد
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $this->ensureActive($wallet);

            if (! $wallet->hasSufficientBalance($amount)) {
                throw new InsufficientBalanceException;
            }

            $wallet->decrement('balance', $amount);

            return $this->record($wallet, TransactionType::Debit, $amount, $description, null, $meta);
        });
    }

    /**
     * سحب رصيد (افتراضي في مشروع التخرج: يُخصم من الرصيد ويُسجَّل كمعاملة دون تحويل بنكي فعلي)
     */
    public function withdraw(Company $company, float $amount, ?string $description = null): Transaction
    {
        return $this->debit(
            $this->getWallet($company),
            $amount,
            $description ?: 'سحب من المحفظة',
            ['kind' => 'withdrawal'],
        );
    }

    // ==================== Internals ====================

    private function ensureActive(Wallet $wallet): void
    {
        if (! $wallet->is_active) {
            throw new ApiException('المحفظة موقوفة.', 403, 'wallet_inactive');
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function record(Wallet $wallet, TransactionType $type, float $amount, string $description, ?int $paymentId, array $meta): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'payment_id' => $paymentId,
            'type' => $type->value,
            'amount' => $amount,
            'currency' => $wallet->currency,
            'status' => 'completed',
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
