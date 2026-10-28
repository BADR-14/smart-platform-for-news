import { useQuery } from '@tanstack/react-query';
import {
  BadgeCheck,
  BarChart3,
  Building2,
  ChevronLeft,
  CircleCheckBig,
  Flag,
  ListChecks,
  Newspaper,
  Package,
  PieChart,
  RefreshCw,
  ShieldAlert,
  Sparkles,
  Tags,
  TrendingUp,
  Users,
  Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { AreaTrendChart } from '@/components/charts/AreaTrendChart';
import { toChartRows } from '@/components/charts/chart-utils';
import { DonutChart } from '@/components/charts/DonutChart';
import { Sparkline } from '@/components/charts/Sparkline';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { CountUp } from '@/components/ui/CountUp';
import { ErrorState, LoadingState, Skeleton } from '@/components/ui/States';
import { useAuth } from '@/features/auth/useAuth';
import { cn } from '@/lib/utils/cn';
import { formatMoney, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import { adminKeys } from '../keys';

export function AdminOverviewPage() {
  const { account } = useAuth();
  const { data, isLoading, isFetching, error, refetch } = useQuery({ queryKey: adminKeys.overview, queryFn: adminApi.overview });

  if (error) return <ErrorState error={error} onRetry={() => refetch()} />;

  const stats = data?.statistics;
  const charts = data?.charts;
  const pendingTotal = (stats?.pending_verifications ?? 0) + (stats?.pending_reports ?? 0) + (stats?.under_review_posts ?? 0);
  const otherPosts = Math.max(0, (stats?.posts ?? 0) - (stats?.published_posts ?? 0) - (stats?.under_review_posts ?? 0));
  const publishedShare = stats && stats.posts > 0 ? Math.round((stats.published_posts / stats.posts) * 100) : 0;

  return (
    <>
      <header className="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-ink">نظرة عامة</h1>
          <p className="mt-1 text-sm text-muted">
            أهلاً {account?.name ?? ''}،{' '}
            {isLoading
              ? 'جارٍ تحميل حالة المنصة…'
              : pendingTotal > 0
                ? `لديك ${formatNumber(pendingTotal)} عنصراً بانتظار إجراء منك.`
                : 'لا توجد عناصر بانتظار المراجعة.'}
          </p>
        </div>
        <button
          type="button"
          onClick={() => refetch()}
          disabled={isFetching}
          className="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-surface px-3.5 text-sm font-semibold text-ink-soft transition-colors hover:border-line-strong hover:text-ink disabled:opacity-60"
        >
          <RefreshCw className={cn('size-4', isFetching && 'animate-spin')} />
          تحديث
        </button>
      </header>

      {/* لوح المؤشرات الداكن: أرقام المنصة مع اتجاه كل منها خلال السنة */}
      <section className="relative mb-6 overflow-hidden rounded-(--radius-card) bg-graphite-900 text-white shadow-(--shadow-lift)">
        <div aria-hidden className="bg-grid pointer-events-none absolute inset-0" />
        <div aria-hidden className="pointer-events-none absolute -top-24 start-1/4 size-72 rounded-full bg-accent-500/15 blur-3xl" />

        <div className="stagger relative grid divide-white/10 sm:grid-cols-2 sm:divide-x sm:divide-x-reverse xl:grid-cols-4">
          <Kpi
            label="المستخدمون"
            icon={<Users className="size-4" />}
            value={stats?.users}
            trend={charts?.users_growth}
            to="/admin/users"
            loading={isLoading}
          />
          <Kpi
            label="الشركات"
            icon={<Building2 className="size-4" />}
            value={stats?.companies}
            trend={charts?.companies_growth}
            to="/admin/companies"
            loading={isLoading}
          />
          <Kpi
            label="المنشورات"
            icon={<Newspaper className="size-4" />}
            value={stats?.posts}
            trend={charts?.news_growth}
            to="/admin/posts"
            loading={isLoading}
          />
          <Kpi
            label="أرصدة المحافظ"
            icon={<Wallet className="size-4" />}
            value={stats?.wallets_balance}
            format={(value) => formatMoney(value)}
            loading={isLoading}
            footnote={stats ? `${formatNumber(stats.plans)} باقة نشطة` : undefined}
          />
        </div>
      </section>

      <div className="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        {/* قائمة العمل: ما ينتظر قراراً من الإدارة */}
        <Card>
          <CardHeader
            icon={<ListChecks className="size-5" />}
            title="قائمة العمل"
            description="ما ينتظر قراراً منك، مرتّباً حسب الأولوية."
            action={
              <span
                className={cn(
                  'ltr rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
                  pendingTotal > 0 ? 'bg-accent-50 text-accent-700' : 'bg-success-50 text-success-700',
                )}
              >
                {formatNumber(pendingTotal)}
              </span>
            }
          />
          <ul className="stagger divide-y divide-line">
            <QueueRow
              to="/admin/reports?status=pending"
              icon={<Flag className="size-5" />}
              tone="danger"
              priority="عاجل"
              title="بلاغات معلّقة"
              description="بلاغات على منشورات أو حسابات"
              count={stats?.pending_reports}
              total={pendingTotal}
              loading={isLoading}
            />
            <QueueRow
              to="/admin/verification?status=pending"
              icon={<BadgeCheck className="size-5" />}
              tone="warning"
              priority="مهم"
              title="طلبات توثيق"
              description="حسابات وشركات تنتظر مراجعة وثائقها"
              count={stats?.pending_verifications}
              total={pendingTotal}
              loading={isLoading}
            />
            <QueueRow
              to="/admin/posts?status=under_review"
              icon={<ShieldAlert className="size-5" />}
              tone="neutral"
              priority="عادي"
              title="منشورات قيد المراجعة"
              description="منشورات أوقفتها الإدارة مؤقتاً"
              count={stats?.under_review_posts}
              total={pendingTotal}
              loading={isLoading}
            />
          </ul>
        </Card>

        <Card>
          <CardHeader icon={<PieChart className="size-5" />} title="حالة المنشورات" description="توزيع كل منشورات المنصة." />
          <CardBody>
            {isLoading || !stats ? (
              <LoadingState />
            ) : (
              <>
                <DonutChart
                  centerLabel="منشور"
                  slices={[
                    { key: 'published', label: 'ظاهرة', value: stats.published_posts, color: 'var(--color-graphite-800)' },
                    { key: 'review', label: 'قيد المراجعة', value: stats.under_review_posts, color: 'var(--color-accent-500)' },
                    { key: 'other', label: 'مسودات ومؤرشفة', value: otherPosts, color: 'var(--color-graphite-200)' },
                  ]}
                />
                <div className="mt-5 flex items-center gap-3 rounded-xl bg-canvas px-4 py-3">
                  <Sparkles className="size-5 shrink-0 text-accent-600" />
                  <p className="flex-1 text-sm text-ink-soft">
                    <span className="ltr font-bold text-ink">{publishedShare}%</span> من المنشورات ظاهرة للجمهور، منها{' '}
                    <Link to="/admin/plans?tab=featured" className="font-bold text-accent-700 hover:underline">
                      {formatNumber(stats.featured_posts)} مميّزة
                    </Link>{' '}
                    حالياً.
                  </p>
                </div>
              </>
            )}
          </CardBody>
        </Card>
      </div>

      <Card className="mb-6">
        <CardHeader
          icon={<TrendingUp className="size-5" />}
          title={`النمو خلال ${charts?.year ?? new Date().getFullYear()}`}
          description="المستخدمون والمنشورات والشركات الجديدة كل شهر."
          action={
            <Link to="/admin/analytics" className="inline-flex items-center gap-1 text-sm font-semibold text-accent-700 hover:underline">
              التقارير التفصيلية
              <ChevronLeft className="size-4" />
            </Link>
          }
        />
        <CardBody>
          {isLoading || !charts ? (
            <LoadingState />
          ) : (
            <AreaTrendChart
              data={toChartRows(charts.labels, {
                users: charts.users_growth,
                news: charts.news_growth,
                companies: charts.companies_growth,
              })}
              series={[
                { key: 'users', label: 'المستخدمون', color: 'var(--color-graphite-700)' },
                { key: 'news', label: 'المنشورات', color: 'var(--color-accent-500)' },
                { key: 'companies', label: 'الشركات', color: 'var(--color-brand-500)' },
              ]}
            />
          )}
        </CardBody>
      </Card>

      <section aria-label="وصول سريع" className="stagger grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Shortcut to="/admin/plans" icon={<Package className="size-5" />} label="الباقات والتمييز" />
        <Shortcut to="/admin/categories" icon={<Tags className="size-5" />} label="التصنيفات والوسوم" />
        <Shortcut to="/admin/analytics" icon={<BarChart3 className="size-5" />} label="التقارير" />
        <Shortcut to="/admin/posts" icon={<CircleCheckBig className="size-5" />} label="إدارة المنشورات" />
      </section>
    </>
  );
}

interface KpiProps {
  label: string;
  icon: ReactNode;
  value: number | undefined;
  format?: (value: number) => string;
  /** القيم الشهرية الجديدة خلال السنة: تُرسم خط اتجاه ويُحسب منها مجموع السنة. */
  trend?: number[];
  footnote?: string;
  to?: string;
  loading: boolean;
}

function Kpi({ label, icon, value, format, trend, footnote, to, loading }: KpiProps) {
  const addedThisYear = trend?.reduce((sum, item) => sum + item, 0);

  const body = (
    <>
      <div className="flex items-center gap-2 text-xs font-semibold text-graphite-300">
        <span className="flex size-7 items-center justify-center rounded-lg bg-white/10 text-accent-400">{icon}</span>
        {label}
      </div>

      <div className="mt-3 flex items-end justify-between gap-3">
        {loading ? (
          <Skeleton className="h-9 w-20 opacity-25" />
        ) : (
          <p className="ltr text-3xl font-bold tabular-nums tracking-tight">
            <CountUp value={value ?? 0} format={format} />
          </p>
        )}
        {trend && !loading && <Sparkline values={trend} color="var(--color-accent-400)" width={96} height={34} />}
      </div>

      <p className="mt-2 text-xs text-graphite-300">
        {addedThisYear !== undefined ? (
          <>
            <span className="ltr font-bold text-white">+{formatNumber(addedThisYear)}</span> هذه السنة
          </>
        ) : (
          (footnote ?? ' ')
        )}
      </p>
    </>
  );

  const className = 'block border-white/10 p-5 transition-colors max-sm:border-b';

  return to ? (
    <Link to={to} className={cn(className, 'hover:bg-white/5')}>
      {body}
    </Link>
  ) : (
    <div className={className}>{body}</div>
  );
}

const queueTones = {
  danger: { chip: 'bg-danger-50 text-danger-600', bar: 'bg-danger-600', priority: 'bg-danger-50 text-danger-700' },
  warning: { chip: 'bg-warning-50 text-warning-700', bar: 'bg-warning-600', priority: 'bg-warning-50 text-warning-700' },
  neutral: { chip: 'bg-graphite-100 text-graphite-700', bar: 'bg-graphite-600', priority: 'bg-graphite-100 text-graphite-600' },
} as const;

interface QueueRowProps {
  to: string;
  icon: ReactNode;
  tone: keyof typeof queueTones;
  priority: string;
  title: string;
  description: string;
  count: number | undefined;
  /** مجموع كل العناصر المنتظرة: يحدد طول شريط حصة هذا البند. */
  total: number;
  loading: boolean;
}

function QueueRow({ to, icon, tone, priority, title, description, count, total, loading }: QueueRowProps) {
  const style = queueTones[tone];
  const value = count ?? 0;
  const share = total > 0 ? Math.round((value / total) * 100) : 0;

  return (
    <li>
      <Link to={to} className="group flex items-center gap-4 px-5 py-4 transition-colors hover:bg-graphite-50">
        <span className={cn('flex size-11 shrink-0 items-center justify-center rounded-xl', value > 0 ? style.chip : 'bg-graphite-50 text-graphite-400')}>
          {icon}
        </span>

        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <p className="truncate font-bold text-ink">{title}</p>
            {value > 0 && <span className={cn('rounded-md px-1.5 py-0.5 text-[10px] font-bold', style.priority)}>{priority}</span>}
          </div>
          <p className="mt-0.5 truncate text-xs text-muted">{description}</p>
          <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-graphite-100">
            <div className={cn('h-full rounded-full transition-[width] duration-700 ease-out', style.bar)} style={{ width: `${share}%` }} />
          </div>
        </div>

        {loading ? (
          <Skeleton className="h-8 w-9" />
        ) : (
          <span className={cn('ltr w-10 text-end text-2xl font-bold tabular-nums', value > 0 ? 'text-ink' : 'text-graphite-300')}>
            {formatNumber(value)}
          </span>
        )}
        <ChevronLeft className="size-4 shrink-0 text-graphite-300 transition-transform duration-200 group-hover:-translate-x-1 group-hover:text-accent-600" />
      </Link>
    </li>
  );
}

function Shortcut({ to, icon, label }: { to: string; icon: ReactNode; label: string }) {
  return (
    <Link
      to={to}
      className="group flex items-center gap-3 rounded-(--radius-card) border border-line bg-surface px-4 py-3.5 transition-all duration-200 hover:border-graphite-900 hover:shadow-(--shadow-lift)"
    >
      <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-graphite-100 text-graphite-700 transition-colors group-hover:bg-graphite-900 group-hover:text-accent-400">
        {icon}
      </span>
      <span className="flex-1 truncate text-sm font-semibold text-ink">{label}</span>
      <ChevronLeft className="size-4 text-graphite-300 transition-transform duration-200 group-hover:-translate-x-1" />
    </Link>
  );
}
