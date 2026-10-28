<?php

namespace App\Services;

use App\Models\PlatformSetting;

/**
 * إعدادات المنصة التي يتحكم بها الأدمن.
 * القيمة المخزّنة في قاعدة البيانات تغلب القيمة الافتراضية في config/platform.php.
 */
class PlatformSettingsService
{
    public const FEATURED_POST_PRICE = 'featured_post_price';

    public const FEATURED_POST_DURATION_DAYS = 'featured_post_duration_days';

    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    /**
     * @return array{featured_post_price: float, featured_post_duration_days: int}
     */
    public function all(): array
    {
        return [
            self::FEATURED_POST_PRICE => $this->featuredPostPrice(),
            self::FEATURED_POST_DURATION_DAYS => $this->featuredPostDurationDays(),
        ];
    }

    public function featuredPostPrice(): float
    {
        return round((float) $this->get(self::FEATURED_POST_PRICE), 2);
    }

    public function featuredPostDurationDays(): int
    {
        return max(1, (int) $this->get(self::FEATURED_POST_DURATION_DAYS));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{featured_post_price: float, featured_post_duration_days: int}
     */
    public function update(array $values): array
    {
        foreach (array_intersect_key($values, config('platform.settings')) as $key => $value) {
            PlatformSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        $this->cache = null;

        return $this->all();
    }

    private function get(string $key): mixed
    {
        $this->cache ??= PlatformSetting::pluck('value', 'key')->all();

        return $this->cache[$key] ?? config("platform.settings.{$key}");
    }
}
