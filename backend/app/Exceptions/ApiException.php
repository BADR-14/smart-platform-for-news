<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * أساس كل استثناءات منطق العمل (Business Rules).
 * أي استثناء يرث منه يتحول تلقائياً إلى رد JSON موحّد بدون try/catch داخل الـ Controllers.
 */
class ApiException extends Exception
{
    protected int $status = 422;

    /**
     * رمز ثابت تعتمد عليه الواجهات لتمييز نوع الخطأ (بدل مقارنة نص الرسالة)
     */
    protected ?string $errorCode = null;

    /**
     * @param  array<string, mixed>  $extra  بيانات إضافية تُدمج مع الرد
     */
    public function __construct(
        ?string $message = null,
        ?int $status = null,
        ?string $errorCode = null,
        protected array $extra = [],
    ) {
        parent::__construct($message ?? $this->message);

        $this->status = $status ?? $this->status;
        $this->errorCode = $errorCode ?? $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            ...$this->extra,
        ], fn ($value) => $value !== null), $this->status);
    }
}
