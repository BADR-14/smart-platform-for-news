<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * شكل الردود الموحّد لكل الـ API:
 *  - نجاح مع بيانات    : { "message"?: "...", "data": ... }
 *  - نجاح بدون بيانات  : { "message": "..." }
 *  - قائمة مقسّمة لصفحات: { "data": [...], "meta": { current_page, last_page, per_page, total }, ...extra }
 *  - الأخطاء            : { "message": "...", "code"?: "...", "errors"?: {...} }
 */
abstract class Controller
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /**
     * @param  class-string<JsonResource>|null  $resource  يُطبَّق على العناصر التي ليست Resource أصلاً
     * @param  array<string, mixed>  $extra  مفاتيح إضافية بجانب data و meta (مثل stats)
     */
    protected function paginated(LengthAwarePaginator $paginator, ?string $resource = null, array $extra = []): JsonResponse
    {
        $items = collect($paginator->items())->map(
            fn ($item) => $resource && ! $item instanceof JsonResource ? new $resource($item) : $item
        );

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            ...$extra,
        ]);
    }
}
