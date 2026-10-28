import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Archive, CheckCircle2, FileSearch, Flag, Newspaper, Sparkles, Trash2, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { ReasonDialog } from '@/components/ui/ReasonDialog';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatCard } from '@/components/ui/StatCard';
import { useCategories } from '@/features/shared/categories';
import { postStatusMeta, postTypeLabels } from '@/features/shared/labels';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatDate, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { AdminPost, ModerationStatus, PostFilters } from '../api';
import { adminKeys } from '../keys';

type Dialog =
  | { type: 'moderate'; post: AdminPost; status: 'under_review' | 'rejected' }
  | { type: 'restore' | 'archive' | 'delete'; post: AdminPost }
  | null;

const moderationCopy = {
  under_review: { title: 'وضع المنشور قيد المراجعة', confirm: 'وضع قيد المراجعة', text: 'سيُخفى المنشور مؤقتاً حتى تنتهي مراجعته.' },
  rejected: { title: 'رفض المنشور', confirm: 'رفض المنشور', text: 'سيُخفى المنشور ويُبلَّغ صاحبه بالرفض.' },
} as const;

export function PostsPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status', 'category_id', 'date_filter'] as const);
  const [dialog, setDialog] = useState<Dialog>(null);
  const categories = useCategories('post');

  const params = compact({ page, search, ...filters }) as PostFilters;

  const query = useQuery({
    queryKey: [...adminKeys.posts, params],
    queryFn: () => adminApi.posts(params),
    placeholderData: keepPreviousData,
  });

  const close = () => setDialog(null);
  const invalidate = [adminKeys.posts, adminKeys.overview, adminKeys.reports];

  const setStatus = useActionMutation({
    mutationFn: ({ id, status, note }: { id: number; status: ModerationStatus; note?: string }) =>
      adminApi.updatePostStatus(id, status, note),
    invalidate,
    onSuccess: close,
  });
  const remove = useActionMutation({ mutationFn: adminApi.deletePost, invalidate, onSuccess: close });

  const columns: Column<AdminPost>[] = [
    {
      key: 'title',
      header: 'المنشور',
      className: 'max-w-72',
      cell: (post) => (
        <div className="min-w-0">
          <p className="flex items-center gap-1.5 font-medium text-ink">
            <span className="truncate">{post.title}</span>
            {post.is_featured && <Sparkles className="size-3.5 shrink-0 text-accent-500" aria-label="منشور مميز" />}
          </p>
          <p className="truncate text-xs text-muted">
            {postTypeLabels[post.post_type]} · {post.category ?? 'بدون تصنيف'}
          </p>
          {post.moderation_note && <p className="mt-1 truncate text-xs text-warning-700">ملاحظة: {post.moderation_note}</p>}
        </div>
      ),
    },
    {
      key: 'author',
      header: 'الناشر',
      cell: (post) => (
        <div>
          <p className="text-ink">{post.author?.name ?? '—'}</p>
          <p className="text-xs text-muted">{post.author?.type === 'company' ? 'شركة' : 'مستخدم'}</p>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'الحالة',
      cell: (post) => {
        const meta = postStatusMeta[post.status];
        return (
          <div className="flex flex-col items-start gap-1">
            <Badge tone={meta.tone}>{meta.label}</Badge>
            {post.pending_reports_count > 0 && (
              <Badge tone="danger">
                <Flag className="size-3" />
                {post.pending_reports_count} بلاغ
              </Badge>
            )}
          </div>
        );
      },
    },
    { key: 'views', header: 'المشاهدات', align: 'center', cell: (post) => formatNumber(post.views_count) },
    { key: 'created', header: 'تاريخ النشر', cell: (post) => <span className="text-muted">{formatDate(post.created_at)}</span> },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (post) => (
        <div className="flex justify-end gap-1">
          {post.status === 'published' ? (
            <>
              <Button variant="ghost" size="icon" title="وضع قيد المراجعة" aria-label="وضع قيد المراجعة" onClick={() => setDialog({ type: 'moderate', post, status: 'under_review' })}>
                <FileSearch className="size-4 text-warning-600" />
              </Button>
              <Button variant="ghost" size="icon" title="رفض" aria-label="رفض المنشور" onClick={() => setDialog({ type: 'moderate', post, status: 'rejected' })}>
                <XCircle className="size-4 text-danger-600" />
              </Button>
              <Button variant="ghost" size="icon" title="أرشفة" aria-label="أرشفة المنشور" onClick={() => setDialog({ type: 'archive', post })}>
                <Archive className="size-4" />
              </Button>
            </>
          ) : (
            post.status !== 'draft' && (
              <Button variant="ghost" size="icon" title="إعادة النشر" aria-label="إعادة نشر المنشور" onClick={() => setDialog({ type: 'restore', post })}>
                <CheckCircle2 className="size-4 text-success-600" />
              </Button>
            )
          )}
          <Button variant="ghost" size="icon" title="حذف" aria-label="حذف المنشور" onClick={() => setDialog({ type: 'delete', post })}>
            <Trash2 className="size-4 text-danger-600" />
          </Button>
        </div>
      ),
    },
  ];

  const stats = query.data?.stats;
  const moderation = dialog?.type === 'moderate' ? moderationCopy[dialog.status] : null;

  return (
    <>
      <PageHeader
        title="المنشورات"
        description="المنشورات تُنشر فوراً. يمكنك وضع المنشور قيد المراجعة أو رفضه عند وجود مخالفة."
      />

      <section className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="كل المنشورات" value={formatNumber(stats?.total_news)} icon={<Newspaper className="size-5" />} loading={query.isLoading} />
        <StatCard label="منشورة" value={formatNumber(stats?.published)} icon={<CheckCircle2 className="size-5" />} tone="success" loading={query.isLoading} />
        <StatCard label="قيد المراجعة" value={formatNumber(stats?.under_review)} icon={<FileSearch className="size-5" />} tone="warning" loading={query.isLoading} />
        <StatCard label="مرفوضة" value={formatNumber(stats?.rejected)} icon={<XCircle className="size-5" />} tone="danger" loading={query.isLoading} />
      </section>

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث بعنوان المنشور…" />
          <Select aria-label="الحالة" wrapperClassName="w-full sm:w-40" value={filters.status} onChange={(event) => setFilter('status', event.target.value)}>
            <option value="">كل الحالات</option>
            {Object.entries(postStatusMeta).map(([value, meta]) => (
              <option key={value} value={value}>
                {meta.label}
              </option>
            ))}
          </Select>
          <Select aria-label="التصنيف" wrapperClassName="w-full sm:w-48" value={filters.category_id} onChange={(event) => setFilter('category_id', event.target.value)}>
            <option value="">كل التصنيفات</option>
            {categories.data?.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </Select>
          <Select aria-label="الفترة" wrapperClassName="w-full sm:w-40" value={filters.date_filter} onChange={(event) => setFilter('date_filter', event.target.value)}>
            <option value="">كل الفترات</option>
            <option value="last_7">آخر 7 أيام</option>
            <option value="last_30">آخر 30 يوماً</option>
            <option value="last_90">آخر 90 يوماً</option>
          </Select>
        </div>

        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(post) => post.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد منشورات"
        />
      </Card>

      <ReasonDialog
        open={dialog?.type === 'moderate'}
        onClose={close}
        title={moderation?.title ?? ''}
        description={
          <>
            <strong>{dialog?.post.title}</strong>
            <br />
            {moderation?.text}
          </>
        }
        label="ملاحظة لصاحب المنشور"
        placeholder="سبب الإجراء (يظهر لصاحب المنشور)"
        confirmLabel={moderation?.confirm ?? ''}
        loading={setStatus.isPending}
        onSubmit={(note) => dialog?.type === 'moderate' && setStatus.mutate({ id: dialog.post.id, status: dialog.status, note })}
      />

      <ConfirmDialog
        open={dialog?.type === 'restore' || dialog?.type === 'archive'}
        onClose={close}
        tone="primary"
        title={dialog?.type === 'archive' ? 'أرشفة المنشور' : 'إعادة نشر المنشور'}
        message={
          dialog?.type === 'archive' ? (
            <>
              سيُخفى <strong>{dialog.post.title}</strong> من المنصة ويمكن إعادة نشره لاحقاً.
            </>
          ) : (
            <>
              سيعود <strong>{dialog?.post.title}</strong> للظهور على المنصة.
            </>
          )
        }
        confirmLabel={dialog?.type === 'archive' ? 'أرشفة' : 'إعادة النشر'}
        loading={setStatus.isPending}
        onConfirm={() =>
          dialog && setStatus.mutate({ id: dialog.post.id, status: dialog.type === 'archive' ? 'archived' : 'published' })
        }
      />

      <ConfirmDialog
        open={dialog?.type === 'delete'}
        onClose={close}
        title="حذف المنشور"
        message={
          <>
            سيُحذف <strong>{dialog?.post.title}</strong> نهائياً ويُبلَّغ صاحبه. لا يمكن التراجع عن هذا الإجراء.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => dialog && remove.mutate(dialog.post.id)}
      />
    </>
  );
}
