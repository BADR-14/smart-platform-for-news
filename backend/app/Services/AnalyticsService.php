<?php

namespace App\Services;

use App\Enums\Account\AccountRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقارير وإحصائيات لوحة الأدمن
 */
class AnalyticsService
{
    private const MONTHS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    /** @var array<string, Collection<string, int>> */
    private array $memo = [];

    /**
     * @return array<string, int>
     */
    public function getOverviewCounters(): array
    {
        return [
            'total_users' => DB::table('accounts')->where('account_type_id', AccountRole::User->value)->whereNull('deleted_at')->count(),
            'total_companies' => DB::table('accounts')->where('account_type_id', AccountRole::Company->value)->whereNull('deleted_at')->count(),
            'total_news' => DB::table('posts')->whereNull('deleted_at')->count(),
            'total_reports' => DB::table('reports')->count(),
        ];
    }

    /**
     * نمو المستخدمين والمنشورات على أشهر سنة معيّنة (الافتراضي: السنة الحالية)
     *
     * @return array{year: int, labels: list<string>, users_growth: list<int>, news_growth: list<int>, companies_growth: list<int>}
     */
    public function getChartsData(?int $year = null): array
    {
        $year ??= now()->year;

        $series = fn (string $table) => $this->monthlyCounts($table)
            ->filter(fn ($count, $key) => str_starts_with($key, "{$year}-"));

        $fill = fn (Collection $counts) => collect(range(1, 12))
            ->map(fn (int $month) => (int) ($counts[sprintf('%d-%02d', $year, $month)] ?? 0))
            ->all();

        return [
            'year' => $year,
            'labels' => array_values(self::MONTHS),
            'users_growth' => $fill($series('accounts')),
            'news_growth' => $fill($series('posts')),
            'companies_growth' => $fill($series('companies')),
        ];
    }

    /**
     * جدول شهري (الأحدث أولاً) بعدد المستخدمين والشركات والمنشورات والبلاغات الجديدة في كل شهر
     *
     * @param  array{year?: int|string|null, month?: int|string|null}  $filters
     * @return array{data: list<array<string, mixed>>, current_page: int, last_page: int, per_page: int, total: int}
     */
    public function getMonthlyReportsTable(array $filters, int $page = 1, int $perPage = 12): array
    {
        $tables = [
            'users_count' => $this->monthlyCounts('accounts'),
            'companies_count' => $this->monthlyCounts('companies'),
            'news_count' => $this->monthlyCounts('posts'),
            'reports_count' => $this->monthlyCounts('reports'),
        ];

        $year = filled($filters['year'] ?? null) ? (int) $filters['year'] : null;
        $month = filled($filters['month'] ?? null) ? (int) $filters['month'] : null;

        // كل الأشهر التي فيها نشاط في أي جدول
        $rows = collect($tables)
            ->flatMap(fn (Collection $counts) => $counts->keys())
            ->unique()
            ->map(fn (string $key) => [
                'year' => (int) substr($key, 0, 4),
                'month_number' => (int) substr($key, 5, 2),
                'key' => $key,
            ])
            ->when($year, fn (Collection $c) => $c->where('year', $year))
            ->when($month, fn (Collection $c) => $c->where('month_number', $month))
            ->sortByDesc('key')
            ->values();

        $total = $rows->count();
        $page = max(1, $page);

        $data = $rows->forPage($page, $perPage)->map(fn (array $row) => [
            'year' => $row['year'],
            'month_number' => $row['month_number'],
            'month_name' => self::MONTHS[$row['month_number']],
            ...collect($tables)->map(fn (Collection $counts) => (int) ($counts[$row['key']] ?? 0))->all(),
        ])->values()->all();

        return [
            'data' => $data,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    /**
     * السنوات التي فيها بيانات (لقائمة الفلترة)
     *
     * @return list<int>
     */
    public function getAvailableYears(): array
    {
        return collect(['accounts', 'posts', 'companies', 'reports'])
            ->flatMap(fn (string $table) => $this->monthlyCounts($table)->keys())
            ->map(fn (string $key) => (int) substr($key, 0, 4))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * عدد السجلات المنشأة في كل شهر، بالمفتاح "YYYY-MM" (استعلام مجمَّع واحد لكل جدول)
     *
     * @return Collection<string, int>
     */
    private function monthlyCounts(string $table): Collection
    {
        return $this->memo[$table] ??= DB::table($table)
            ->when($table === 'accounts', fn ($q) => $q->where('account_type_id', AccountRole::User->value))
            ->whereNotNull('created_at')
            ->selectRaw("{$this->yearMonthExpression()} as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->map(fn ($total) => (int) $total);
    }

    /**
     * صيغة "YYYY-MM" تختلف حسب نوع قاعدة البيانات (MySQL في التشغيل، SQLite في الاختبارات)
     */
    private function yearMonthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }
}
