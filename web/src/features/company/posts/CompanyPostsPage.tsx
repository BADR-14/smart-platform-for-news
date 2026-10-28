import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Archive, Eye, Heart, MessageCircle, Pencil, Plus, Send, Sparkles, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { SearchInput } from '@/components/ui/SearchInput';
import { postStatusMeta, postTypeLabels } from '@/features/shared/labels';
import type { Post } from '@/features/shared/types';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatDate, formatNumber } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { MyPostsFilters } from '../api';
import { FeaturePostDialog } from '../featured/FeaturePostDialog';
import { companyKeys } from '../keys';

type Dialog = { type: 'delete' | 'archive' | 'publish' | 'feature'; post: Post } | null;

export function CompanyPostsPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status'] as const);
  const [dialog, setDialog] = useState<Dialog>(null);

  const params = compact({ page, search, ...filters }) as MyPostsFilters;

  const query = useQuery({
    queryKey: [...companyKeys.posts, params],
    queryFn: () => companyApi.myPosts(params),
    placeholderData: keepPreviousData,
  });

  const close = () => setDialog(null);
  const invalidate = [companyKeys.posts, companyKeys.overview];

  const remove = useActionMutation({ mutationFn: companyApi.deletePost, invalidate, onSuccess: close });
  const changeStatus = useActionMutation({
    mutationFn: ({ id, action }: { id: number; action: 'publish' | 'archive' }) =>
      companyApi.updatePost(id, { action }).then((response) => response.message),
    invalidate,
    onSuccess: close,
  });

  const columns: Column<Post>[] = [
    {
      key: 'post',
      header: 'المنشور',
      className: 'max-w-80',
      cell: (post) => (
        <div className="flex items-center gap-3">
          {post.cover_url ? (
            <img src={post.cover_url} alt="" loading="lazy" className="size-12 shrink-0 rounded-xl border border-line object-cover" />
          ) : (
            <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xs font-semibold text-brand-600">
              {postTypeLabels[post.post_type]}
            </span>
          )}
          <div className="min-w-0">
            <p className="flex items-center gap-1.5 font-medium text-ink">
              <span className="truncate">{post.title}</span>
              {post.is_featured && <Sparkles className="size-3.5 shrink-0 text-accent-500" aria-label="منشور مميز" />}
            </p>
            <p className="truncate text-xs text-muted">
              {postTypeLabels[post.post_type]} · {post.category?.name ?? 'بدون تصنيف'}
            </p>
            {post.moderation_note && <p className="mt-0.5 truncate text-xs text-warning-700">ملاحظة الإدارة: {post.moderation_note}</p>}
          </div>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'الحالة',
      cell: (post) => (
        <div className="flex flex-col items-start gap-1">
          <Badge tone={postStatusMeta[post.status].tone}>{postStatusMeta[post.status].label}</Badge>
          {post.is_featured && <span className="text-[11px] text-accent-700">مميز حتى {formatDate(post.featured_until)}</span>}
        </div>
      ),
    },
    {
      key: 'engagement',
      header: 'التفاعل',
      cell: (post) => (
        <div className="flex items-center gap-3 text-xs text-muted">
          <span className="inline-flex items-center gap-1" title="مشاهدات">
            <Eye className="size-3.5" /> {formatNumber(post.views_count)}
          </span>
          <span className="inline-flex items-center gap-1" title="تفاعلات">
            <Heart className="size-3.5" /> {formatNumber(post.likes_count)}
          </span>
          <span className="inline-flex items-center gap-1" title="تعليقات">
            <MessageCircle className="size-3.5" /> {formatNumber(post.comments_count)}
          </span>
        </div>
      ),
    },
    { key: 'created', header: 'تاريخ الإنشاء', cell: (post) => <span className="text-muted">{formatDate(post.created_at)}</span> },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (post) => (
        <div className="flex justify-end gap-1">
          {post.status === 'published' && !post.is_featured && (
            <Button variant="outline" size="sm" icon={<Sparkles className="size-3.5 text-accent-500" />} onClick={() => setDialog({ type: 'feature', post })}>
              تمييز
            </Button>
          )}
          {(post.status === 'draft' || post.status === 'archived') && (
            <Button variant="ghost" size="icon" title="نشر" aria-label="نشر المنشور" onClick={() => setDialog({ type: 'publish', post })}>
              <Send className="size-4 text-success-600" />
            </Button>
          )}
          {post.status === 'published' && (
            <Button variant="ghost" size="icon" title="أرشفة" aria-label="أرشفة المنشور" onClick={() => setDialog({ type: 'archive', post })}>
              <Archive className="size-4" />
            </Button>
          )}
          <Link to={`/company/posts/${post.id}/edit`} title="تعديل" aria-label="تعديل المنشور">
            <Button variant="ghost" size="icon" tabIndex={-1}>
              <Pencil className="size-4" />
            </Button>
          </Link>
          <Button variant="ghost" size="icon" title="حذف" aria-label="حذف المنشور" onClick={() => setDialog({ type: 'delete', post })}>
            <Trash2 className="size-4 text-danger-600" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="منشوراتي"
        description="كل منشورات شركتك بكل حالاتها."
        actions={
          <Link to="/company/posts/new">
            <Button variant="accent" icon={<Plus className="size-4" />}>
              منشور جديد
            </Button>
          </Link>
        }
      />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث بعنوان المنشور…" />
          <Select aria-label="الحالة" wrapperClassName="w-full sm:w-44" value={filters.status} onChange={(event) => setFilter('status', event.target.value)}>
            <option value="">كل الحالات</option>
            {Object.entries(postStatusMeta).map(([value, meta]) => (
              <option key={value} value={value}>
                {meta.label}
              </option>
            ))}
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
          emptyDescription="أنشئ منشوراً جديداً أو غيّر الفلاتر."
        />
      </Card>

      {dialog?.type === 'feature' && <FeaturePostDialog post={dialog.post} onClose={close} />}

      <ConfirmDialog
        open={dialog?.type === 'publish' || dialog?.type === 'archive'}
        onClose={close}
        tone="primary"
        title={dialog?.type === 'archive' ? 'أرشفة المنشور' : 'نشر المنشور'}
        message={
          dialog?.type === 'archive' ? (
            <>
              سيُخفى <strong>{dialog.post.title}</strong> من المنصة ويمكنك إعادة نشره لاحقاً.
            </>
          ) : (
            <>
              سيظهر <strong>{dialog?.post.title}</strong> للجميع على المنصة.
            </>
          )
        }
        confirmLabel={dialog?.type === 'archive' ? 'أرشفة' : 'نشر'}
        loading={changeStatus.isPending}
        onConfirm={() => dialog && changeStatus.mutate({ id: dialog.post.id, action: dialog.type === 'archive' ? 'archive' : 'publish' })}
      />

      <ConfirmDialog
        open={dialog?.type === 'delete'}
        onClose={close}
        title="حذف المنشور"
        message={
          <>
            سيُحذف <strong>{dialog?.post.title}</strong> نهائياً مع تعليقاته وتفاعلاته.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => dialog && remove.mutate(dialog.post.id)}
      />
    </>
  );
}
