<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Requests\Wallet\TopUpRequest;
use App\Http\Requests\Wallet\TransactionFilterRequest;
use App\Http\Requests\Wallet\WithdrawRequest;
use App\Http\Resources\Wallet\TransactionResource;
use App\Http\Resources\Wallet\WalletResource;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends CompanyAreaController
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * GET /company/wallet — المحفظة مع ملخص الإيداعات والمصروفات
     */
    public function index(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getWallet($this->company($request));

        return $this->ok([
            'wallet' => new WalletResource($wallet),
            'summary' => $this->walletService->getSummary($wallet),
        ]);
    }

    /**
     * POST /company/wallet/topup — بدء عملية شحن عبر Stripe
     */
    public function initiateTopUp(TopUpRequest $request): JsonResponse
    {
        $result = $this->walletService->initiateTopUp(
            company: $this->company($request),
            amount: (float) $request->validated('amount'),
        );

        return $this->created($result);
    }

    /**
     * POST /company/wallet/topup/confirm — تأكيد الشحن بعد نجاح الدفع في الواجهة
     */
    public function confirmTopUp(Request $request): JsonResponse
    {
        $validated = $request->validate(['payment_intent_id' => ['required', 'string', 'max:255']]);

        $wallet = $this->walletService->confirmTopUp($this->company($request), $validated['payment_intent_id']);

        return $this->ok(new WalletResource($wallet), 'تم شحن المحفظة بنجاح.');
    }

    /**
     * POST /company/wallet/withdraw
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $company = $this->company($request);

        $transaction = $this->walletService->withdraw(
            $company,
            (float) $request->validated('amount'),
            $request->validated('description'),
        );

        return $this->ok([
            'transaction' => new TransactionResource($transaction),
            'wallet' => new WalletResource($this->walletService->getWallet($company)->refresh()),
        ], 'تم السحب بنجاح.');
    }

    /**
     * GET /company/wallet/transactions?type=credit&status=completed
     */
    public function transactions(TransactionFilterRequest $request): JsonResponse
    {
        $transactions = $this->walletService->getTransactions(
            company: $this->company($request),
            type: $request->validated('type'),
            status: $request->validated('status'),
            perPage: $request->integer('per_page', 15),
        );

        return $this->paginated($transactions, TransactionResource::class);
    }
}
