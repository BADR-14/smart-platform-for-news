<?php

namespace App\Contracts;

/**
 * قارئ نصوص الوثائق (OCR). التطبيق الفعلي: GoogleVisionService.
 * الفصل بواجهة يسمح بتبديل المزوّد أو تزييفه في الاختبارات دون لمس منطق التوثيق.
 */
interface DocumentTextExtractor
{
    /**
     * هل الخدمة مهيّأة (مفاتيحها موجودة) وجاهزة للاستخدام؟
     */
    public function isAvailable(): bool;

    /**
     * @param  string  $contents  محتوى ملف الصورة (binary)
     * @return array{text: string, confidences: list<float>}
     */
    public function extractText(string $contents): array;
}
