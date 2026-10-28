<?php

namespace App\Jobs;

use App\Contracts\DocumentTextExtractor;
use App\Enums\Account\VerificationStatus;
use App\Models\VerificationRequest;
use App\Services\NotificationService;
use App\Services\Verifications\BaseVerificationService;
use App\Services\Verifications\VerificationDecisionService;
use App\Services\Verifications\VerificationScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * يقرأ وثائق طلب التوثيق آلياً ويقرر:
 *  - نتيجة >= 90         : موافقة فورية
 *  - نتيجة بين 60 و 89   : يُحال للأدمن ليقرر
 *  - نتيجة أقل من 60     : رفض فوري
 * وإذا تعذّرت القراءة الآلية لأي سبب يُحال الطلب للأدمن بدل أن يفشل.
 */
class ProcessVerificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $verificationRequestId) {}

    /**
     * الخدمات تُحقن هنا وليس في الـ constructor لأن الـ Job يُخزَّن في الطابور كنص،
     * واتصالات الشبكة لا تُخزَّن.
     */
    public function handle(
        DocumentTextExtractor $extractor,
        VerificationScoringService $scoringService,
        VerificationDecisionService $decisionService,
        NotificationService $notificationService,
    ): void {
        $verificationRequest = VerificationRequest::with(['documents', 'account.user', 'account.company'])
            ->find($this->verificationRequestId);

        // الطلب حُذف أو راجعه الأدمن قبل أن يصل دوره في الطابور
        if (! $verificationRequest || $verificationRequest->status !== VerificationStatus::Pending->value) {
            return;
        }

        $extracted = $this->extractDocuments($verificationRequest, $extractor);

        if ($extracted === null) {
            $notificationService->notifyVerificationRequested($verificationRequest);

            return;
        }

        $account = $verificationRequest->account;

        [$name, $idNumber] = $verificationRequest->isForCompany()
            ? [(string) $account->company?->name, $account->company?->tax_id]
            : [(string) $account->name, $account->user?->national_id];

        $result = $scoringService->calculateScore($extracted['text'], $name, $idNumber, $extracted['confidences']);

        $status = match (true) {
            $result['score'] >= (int) config('platform.verification.auto_approve_score', 90) => VerificationStatus::Approved,
            $result['score'] >= (int) config('platform.verification.manual_review_score', 60) => VerificationStatus::Pending,
            default => VerificationStatus::Rejected,
        };

        $decisionService->apply(
            $verificationRequest,
            $status,
            rejectionReasons: $result['failed_reasons'],
            confidenceScore: $result['score'],
        );

        if ($status === VerificationStatus::Pending) {
            $notificationService->notifyVerificationRequested($verificationRequest);
        }
    }

    /**
     * @return array{text: string, confidences: list<float>}|null null = تعذّرت القراءة الآلية (مراجعة يدوية)
     */
    private function extractDocuments(VerificationRequest $verificationRequest, DocumentTextExtractor $extractor): ?array
    {
        // التوثيق الآلي معطّل من الإعدادات، أو لا يوجد مفتاح Google Vision: مراجعة يدوية
        if (! config('platform.verification.auto_enabled', true) || ! $extractor->isAvailable()) {
            return null;
        }

        $fullText = '';
        $confidences = [];

        try {
            foreach ($verificationRequest->documents as $document) {
                $contents = Storage::disk(BaseVerificationService::DOCUMENTS_DISK)->get($document->file_url);

                if ($contents === null) {
                    return null;
                }

                $result = $extractor->extractText($contents);

                $document->update(['extracted_text' => $result['text']]);

                $fullText .= ' '.$result['text'];
                $confidences = [...$confidences, ...$result['confidences']];
            }
        } catch (Throwable $e) {
            Log::warning('Verification OCR failed, falling back to manual review', [
                'verification_request_id' => $verificationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        // لا نرفض آلياً وثيقة لم نستطع قراءتها إطلاقاً (PDF مثلاً)
        return trim($fullText) === '' ? null : ['text' => $fullText, 'confidences' => $confidences];
    }
}
