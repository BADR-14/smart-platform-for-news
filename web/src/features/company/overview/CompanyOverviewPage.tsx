import { useQuery } from '@tanstack/react-query';
import { ChevronLeft, Eye, FileText, Heart, MessageCircle, PieChart, Plus, Share2, Sparkles, Users, Wallet } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { DonutChart } from '@/components/charts/DonutChart';
import { ProgressRing } from '@/components/charts/ProgressRing';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { HeroBanner } from '@/components/ui/HeroBanner';
import { StatCard } from '@/components/ui/StatCard';
import { EmptyState, ErrorState, LoadingState } from '@/components/ui/States';
import { useAuth } from '@/features/auth/useAuth';
import { postStatusMeta, postTypeLabels } from '@/features/shared/labels';
import type { PostStatus } from '@/features/shared/types';
import { formatCompact, formatDate, formatMoney, formatNumber } from '@/lib/utils/format';
import { companyApi } from '../api';
import { companyKeys } from '../keys';

const statusColors: Record<PostStatus, string> = {
  published: 'var(--color-success-600)',
  draft: 'var(--color-brand-300)',
  under_review: 'var(--color-warning-600)',
  rejected: 'var(--color-danger-600)',
  archived: 'var(--color-info-600)',
};

const statusOrder: PostStatus[] = ['published', 'draft', 'under_review', 'rejected', 'archived'];

export function CompanyOverviewPage() {
  const { account } = useAuth();

  const overview = useQuery({ queryKey: companyKeys.overview, queryFn: companyApi.overview });
  const recent = useQuery({
    queryKey: [...companyKeys.posts, { page: 1, per_page: 5 }],
    queryFn: () => companyApi.myPosts({ page: 1, per_page: 5 }),
  });

  if (overview.error) return <ErrorState error={overview.error} onRetry={() => overview.refetch()} />;

  const data = overview.data;
  const loading = overview.isLoading;
  const icon = 'size-6';
  const plan = data?.active_plan;
  const planTotal = plan?.posts_count ?? 0;
  const planRatio = plan && planTotal > 0 ? plan.posts_remaining / planTotal : 0;

  return (
    <>
      <HeroBanner
        eyebrow="لوحة الشركة"
        title={`أهلاً، ${account?.name ?? ''} 👋`}
        description="تابع أداء منشوراتك، ميّز ما يستحق الظهور، وأدر محفظتك من مكان واحد."
        actions={
          <>
            <Link to="/company/posts/new">
              <Button variant="accent" icon={<Plus className="size-4" />}>
                منشور جديد
              </Button>
            </Link>
            <Link to="/company/featured">
              <Button className="border border-white/20 bg-white/10 text-white backdrop-blur hover:bg-white/20" icon={<Sparkles className="size-4" />}>
                ميّز منشوراً
              </Button>
            </Link>
          </>
        }
        aside={
          <Link
            to="/company/plans"
            className="flex items-center gap-4 rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur transition-colors hover:bg-white/15"
          >
            <ProgressRing value={planRatio} size={104} stroke={10}>
              <span className="ltr text-xl font-bold tabular-nums">{plan ? plan.posts_remaining : 0}</span>
              <span className="text-[10px] text-brand-100">متبقٍّ</span>
            </ProgressRing>
            <div className="max-w-40">
              <p className="text-xs text-accent-200">الباقة النشطة</p>
              <p className="mt-0.5 font-bold">{loading ? '…' : (plan?.name ?? 'لا توجد باقة')}</p>
              <p className="mt-1 text-xs leading-relaxed text-brand-100">
                {plan ? `${plan.posts_remaining} من ${planTotal || '—'} منشوراً مميزاً` : 'اشترك بباقة لتمييز منشوراتك بسعر أوفر.'}
              </p>
            </div>
          </Link>
        }
      />

      <section className="stagger mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="رصيد المحفظة"
          value={data?.wallet.balance ?? 0}
          format={(value) => formatMoney(value, data?.wallet.currency)}
          icon={<Wallet className={icon} />}
          tone="success"
          to="/company/wallet"
          loading={loading}
        />
        <StatCard label="المتابعون" value={data?.followers_count ?? 0} icon={<Users className={icon} />} tone="info" loading={loading} />
        <StatCard
          label="منشورات مميزة حالياً"
          value={data?.featured_active_count ?? 0}
          icon={<Sparkles className={icon} />}
          tone="accent"
          to="/company/featured"
          loading={loading}
        />
        <StatCard label="كل المنشورات" value={data?.posts.total ?? 0} icon={<FileText className={icon} />} tone="brand" to="/company/posts" loading={loading} />
      </section>

      <div className="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]">
        <Card>
          <CardHeader icon={<PieChart className="size-5" />} title="حالة منشوراتي" description="اضغط على أي حالة لعرض منشوراتها." />
          <CardBody>
            {loading || !data ? (
              <LoadingState className="py-10" />
            ) : (
              <DonutChart
                centerLabel="منشور"
                slices={statusOrder.map((status) => ({
                  key: status,
                  label: postStatusMeta[status].label,
                  value: data.posts[status],
                  color: statusColors[status],
                }))}
              />
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="التفاعل مع منشوراتك" description="مجموع ما حصدته كل منشورات الشركة." />
          <CardBody className="stagger grid grid-cols-2 gap-3">
            <EngagementTile icon={<Eye className="size-5" />} label="مشاهدة" value={data?.engagement.views} loading={loading} className="bg-info-50 text-info-600" />
            <EngagementTile icon={<Heart className="size-5" />} label="تفاعل" value={data?.engagement.likes} loading={loading} className="bg-danger-50 text-danger-600" />
            <EngagementTile icon={<MessageCircle className="size-5" />} label="تعليق" value={data?.engagement.comments} loading={loading} className="bg-accent-50 text-accent-700" />
            <EngagementTile icon={<Share2 className="size-5" />} label="مشاركة" value={data?.engagement.shares} loading={loading} className="bg-success-50 text-success-600" />
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader
          icon={<FileText className="size-5" />}
          title="آخر المنشورات"
          action={
            <Link to="/company/posts" className="inline-flex items-center gap-1 text-sm font-semibold text-accent-700 hover:underline">
              عرض الكل
              <ChevronLeft className="size-4" />
            </Link>
          }
        />
        {recent.isLoading ? (
          <LoadingState />
        ) : recent.data?.data.length ? (
          <ul className="stagger divide-y divide-line/70">
            {recent.data.data.map((post) => (
              <li key={post.id} className="group flex items-center gap-4 px-5 py-3.5 transition-colors hover:bg-accent-50/40">
                {post.cover_url ? (
                  <img src={post.cover_url} alt="" loading="lazy" className="h-12 w-20 shrink-0 rounded-xl border border-line object-cover" />
                ) : (
                  <span className="flex h-12 w-20 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <FileText className="size-5" />
                  </span>
                )}
                <div className="min-w-0 flex-1">
                  <Link to={`/company/posts/${post.id}/edit`} className="block truncate font-semibold text-ink group-hover:text-accent-700">
                    {post.title}
                  </Link>
                  <p className="text-xs text-muted">
                    {postTypeLabels[post.post_type]} · {formatDate(post.created_at)}
                  </p>
                </div>
                <div className="hidden items-center gap-3 text-xs text-muted sm:flex">
                  <span className="inline-flex items-center gap-1">
                    <Eye className="size-3.5" /> {formatNumber(post.views_count)}
                  </span>
                  <span className="inline-flex items-center gap-1">
                    <Heart className="size-3.5" /> {formatNumber(post.likes_count)}
                  </span>
                </div>
                <Badge tone={postStatusMeta[post.status].tone}>{postStatusMeta[post.status].label}</Badge>
              </li>
            ))}
          </ul>
        ) : (
          <EmptyState
            title="لم تنشر شيئاً بعد"
            description="أنشئ أول منشور لشركتك ليظهر لمتابعيك."
            action={
              <Link to="/company/posts/new">
                <Button size="sm" variant="accent" icon={<Plus className="size-4" />}>
                  منشور جديد
                </Button>
              </Link>
            }
          />
        )}
      </Card>
    </>
  );
}

interface EngagementTileProps {
  icon: ReactNode;
  label: string;
  value: number | undefined;
  loading: boolean;
  className: string;
}

function EngagementTile({ icon, label, value, loading, className }: EngagementTileProps) {
  return (
    <div className="rounded-2xl border border-line/70 bg-canvas/40 p-4 transition-transform duration-300 hover:-translate-y-0.5">
      <span className={`flex size-10 items-center justify-center rounded-xl ${className}`}>{icon}</span>
      <p className="ltr mt-3 text-end text-2xl font-bold tabular-nums text-ink">{loading ? '…' : formatCompact(value)}</p>
      <p className="text-xs text-muted">{label}</p>
    </div>
  );
}
