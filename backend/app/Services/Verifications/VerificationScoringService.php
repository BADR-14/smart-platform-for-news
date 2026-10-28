<?php

namespace App\Services\Verifications;

use Illuminate\Support\Carbon;

class VerificationScoringService
{
    /**
     * توزيع النتيجة (من 100):
     *  - تطابق الاسم            30
     *  - تطابق رقم الهوية/الضريبة 30
     *  - وجود تاريخ صالح         20
     *  - وضوح الصورة             20
     *
     * @param  list<float>  $confidenceScores  ثقة القراءة لكل صفحة (0..1)
     * @return array{score: int, failed_reasons: list<string>}
     */
    public function calculateScore(string $fullText, string $name, ?string $idNumber, array $confidenceScores): array
    {
        $score = 0;
        $failedReasons = [];

        if ($this->checkNameMatch($fullText, $name)) {
            $score += 30;
        } else {
            $failedReasons[] = 'name_mismatch';
        }

        if ($this->checkIdMatch($fullText, $idNumber)) {
            $score += 30;
        } else {
            $failedReasons[] = 'id_mismatch';
        }

        if ($this->checkDateMatch($fullText)) {
            $score += 20;
        } else {
            $failedReasons[] = 'invalid_date';
        }

        $clarity = $this->calculateImageClarityScore($confidenceScores);
        $score += $clarity;

        if ($clarity < 10) {
            $failedReasons[] = 'low_image_quality';
        }

        return [
            'score' => (int) round($score),
            'failed_reasons' => $failedReasons,
        ];
    }

    /**
     * @param  list<float>  $confidenceScores
     */
    private function calculateImageClarityScore(array $confidenceScores): float
    {
        if (empty($confidenceScores)) {
            return 0;
        }

        $average = array_sum($confidenceScores) / count($confidenceScores);

        return max(0, min(1, $average)) * 20;
    }

    /**
     * كل أجزاء الاسم يجب أن تظهر في النص، دون اشتراط الترتيب أو حالة الأحرف
     */
    private function checkNameMatch(string $fullText, string $name): bool
    {
        $text = $this->normalize($fullText);
        $parts = array_filter(explode(' ', $this->normalize($name)));

        if (empty($parts)) {
            return false;
        }

        foreach ($parts as $part) {
            if (! str_contains($text, $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * مقارنة بالأرقام فقط لأن الوثيقة قد تكتب الرقم بفواصل أو مسافات
     */
    private function checkIdMatch(string $fullText, ?string $id): bool
    {
        $id = preg_replace('/\D+/', '', (string) $id);

        if ($id === '' || $id === null) {
            return false;
        }

        return str_contains(preg_replace('/[^\d]+/', '', $fullText) ?? '', $id);
    }

    private function checkDateMatch(string $fullText): bool
    {
        $patterns = [
            '/\b\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4}\b/',
            '/\b\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2}\b/',
            '/\b\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4}\b/i',
            '/\b[A-Za-z]{3,9}\s+\d{1,2},?\s+\d{4}\b/i',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $fullText, $matches);

            foreach ($matches[0] as $textDate) {
                try {
                    $date = Carbon::parse(str_replace('.', '-', trim($textDate)));

                    // تاريخ منطقي: ليس في المستقبل وليس أقدم من 1900
                    if ($date->isPast() && $date->year >= 1900) {
                        return true;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($value)));
    }
}
