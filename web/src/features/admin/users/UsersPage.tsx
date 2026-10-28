import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Ban, ShieldCheck, Trash2, UserCheck, Users, UserX } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select } from '@/components/ui/FormField';
import { Avatar } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { ReasonDialog } from '@/components/ui/ReasonDialog';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatCard } from '@/components/ui/StatCard';
import { verificationStatusMeta } from '@/features/shared/labels';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatDate, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { AdminUser, UserFilters } from '../api';
import { adminKeys } from '../keys';

type Dialog = { type: 'ban' | 'unban' | 'delete'; user: AdminUser } | null;

export function UsersPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status', 'verification'] as const);
  const [dialog, setDialog] = useState<Dialog>(null);

  const params = compact({ page, search, ...filters }) as UserFilters;

  const query = useQuery({
    queryKey: [...adminKeys.users, params],
    queryFn: () => adminApi.users(params),
    placeholderData: keepPreviousData,
  });

  const close = () => setDialog(null);
  const invalidate = [adminKeys.users, adminKeys.overview];

  const ban = useActionMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => adminApi.banUser(id, reason),
    invalidate,
    onSuccess: close,
  });
  const unban = useActionMutation({ mutationFn: adminApi.unbanUser, invalidate, onSuccess: close });
  const remove = useActionMutation({ mutationFn: adminApi.deleteUser, invalidate, onSuccess: close });

  const columns: Column<AdminUser>[] = [
    {
      key: 'user',
      header: 'المستخدم',
      cell: (user) => (
        <div className="flex items-center gap-3">
          <Avatar name={user.name} src={user.avatar} size="sm" />
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{user.name}</p>
            <p className="ltr truncate text-end text-xs text-muted">{user.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'verification',
      header: 'التوثيق',
      cell: (user) => {
        const meta = verificationStatusMeta[user.verification_status];
        return <Badge tone={meta.tone}>{meta.label}</Badge>;
      },
    },
    {
      key: 'status',
      header: 'الحالة',
      cell: (user) =>
        user.is_active ? (
          <Badge tone="success" dot>
            نشط
          </Badge>
        ) : (
          <span title={user.ban_reason ?? undefined}>
            <Badge tone="danger" dot>
              محظور
            </Badge>
          </span>
        ),
    },
    { key: 'posts', header: 'المنشورات', align: 'center', cell: (user) => formatNumber(user.posts_count) },
    { key: 'created', header: 'تاريخ التسجيل', cell: (user) => <span className="text-muted">{formatDate(user.created_at)}</span> },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (user) => (
        <div className="flex justify-end gap-1.5">
          {user.is_active ? (
            <Button variant="danger-outline" size="sm" icon={<Ban className="size-3.5" />} onClick={() => setDialog({ type: 'ban', user })}>
              حظر
            </Button>
          ) : (
            <Button variant="outline" size="sm" icon={<ShieldCheck className="size-3.5" />} onClick={() => setDialog({ type: 'unban', user })}>
              إلغاء الحظر
            </Button>
          )}
          <Button variant="ghost" size="icon" aria-label="حذف المستخدم" onClick={() => setDialog({ type: 'delete', user })}>
            <Trash2 className="size-4 text-danger-600" />
          </Button>
        </div>
      ),
    },
  ];

  const stats = query.data?.stats;

  return (
    <>
      <PageHeader title="المستخدمون" description="إدارة حسابات المستخدمين: الحظر، إلغاء الحظر، والحذف." />

      <section className="mb-6 grid gap-4 sm:grid-cols-3">
        <StatCard label="إجمالي المستخدمين" value={formatNumber(stats?.total)} icon={<Users className="size-5" />} loading={query.isLoading} />
        <StatCard label="موثّقون" value={formatNumber(stats?.verified)} icon={<UserCheck className="size-5" />} tone="success" loading={query.isLoading} />
        <StatCard label="محظورون" value={formatNumber(stats?.blocked)} icon={<UserX className="size-5" />} tone="danger" loading={query.isLoading} />
      </section>

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث بالاسم أو البريد…" />
          <Select
            aria-label="الحالة"
            wrapperClassName="w-full sm:w-40"
            value={filters.status}
            onChange={(event) => setFilter('status', event.target.value)}
          >
            <option value="">كل الحالات</option>
            <option value="active">نشط</option>
            <option value="blocked">محظور</option>
          </Select>
          <Select
            aria-label="التوثيق"
            wrapperClassName="w-full sm:w-44"
            value={filters.verification}
            onChange={(event) => setFilter('verification', event.target.value)}
          >
            <option value="">كل حالات التوثيق</option>
            {Object.entries(verificationStatusMeta).map(([value, meta]) => (
              <option key={value} value={value}>
                {meta.label}
              </option>
            ))}
          </Select>
        </div>

        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(user) => user.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا يوجد مستخدمون"
          emptyDescription="جرّب تغيير البحث أو الفلاتر."
        />
      </Card>

      <ReasonDialog
        open={dialog?.type === 'ban'}
        onClose={close}
        title="حظر المستخدم"
        description={
          <>
            سيُمنع <strong>{dialog?.user.name}</strong> من تسجيل الدخول وتُنهى كل جلساته، وسيصله إشعار بالسبب.
          </>
        }
        label="سبب الحظر"
        placeholder="مثال: نشر محتوى مخالف"
        confirmLabel="حظر"
        loading={ban.isPending}
        onSubmit={(reason) => dialog && ban.mutate({ id: dialog.user.id, reason })}
      />

      <ConfirmDialog
        open={dialog?.type === 'unban'}
        onClose={close}
        tone="primary"
        title="إلغاء الحظر"
        message={
          <>
            هل تريد إلغاء حظر <strong>{dialog?.user.name}</strong>؟ سيتمكن من تسجيل الدخول مجدداً.
          </>
        }
        confirmLabel="إلغاء الحظر"
        loading={unban.isPending}
        onConfirm={() => dialog && unban.mutate(dialog.user.id)}
      />

      <ConfirmDialog
        open={dialog?.type === 'delete'}
        onClose={close}
        title="حذف المستخدم"
        message={
          <>
            سيُحذف حساب <strong>{dialog?.user.name}</strong> وتُخفى كل منشوراته. لا يمكن التراجع عن هذا الإجراء.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => dialog && remove.mutate(dialog.user.id)}
      />
    </>
  );
}
