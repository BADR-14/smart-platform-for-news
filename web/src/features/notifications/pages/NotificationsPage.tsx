import { CheckCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Tabs } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState, ErrorState, LoadingState } from '@/components/ui/States';
import { useAuth } from '@/features/auth/useAuth';
import { errorMessage } from '@/lib/api/errors';
import { cn } from '@/lib/utils/cn';
import { formatRelative } from '@/lib/utils/format';
import { useNotificationActions, useNotifications } from '../hooks';
import { notificationLink, notificationVisual } from '../notification-meta';

type Filter = 'all' | 'unread';

export function NotificationsPage() {
  const [filter, setFilter] = useState<Filter>('all');
  const [page, setPage] = useState(1);
  const navigate = useNavigate();
  const { account } = useAuth();

  const query = useNotifications(page, { unreadOnly: filter === 'unread' });
  const { markRead, markAllRead, remove } = useNotificationActions();

  const unreadCount = query.data?.unread_count ?? 0;

  return (
    <>
      <PageHeader
        title="الإشعارات"
        description="كل ما يخص حسابك في مكان واحد."
        actions={
          <Button
            variant="outline"
            icon={<CheckCheck className="size-4" />}
            disabled={unreadCount === 0}
            loading={markAllRead.isPending}
            onClick={() =>
              markAllRead.mutate(undefined, {
                onSuccess: () => toast.success('تم تحديد جميع الإشعارات كمقروءة'),
                onError: (error) => toast.error(errorMessage(error)),
              })
            }
          >
            تحديد الكل كمقروء
          </Button>
        }
      />

      <Tabs<Filter>
        className="mb-4"
        value={filter}
        onChange={(value) => {
          setFilter(value);
          setPage(1);
        }}
        items={[
          { value: 'all', label: 'الكل' },
          { value: 'unread', label: 'غير المقروءة', count: unreadCount },
        ]}
      />

      <Card>
        {query.isLoading ? (
          <LoadingState />
        ) : query.error ? (
          <ErrorState error={query.error} onRetry={() => query.refetch()} />
        ) : query.data?.data.length ? (
          <>
            <ul className="divide-y divide-line">
              {query.data.data.map((notification) => {
                const visual = notificationVisual(notification.type);
                const link = account ? notificationLink(notification, account.type) : null;

                return (
                  <li
                    key={notification.id}
                    className={cn('flex items-start gap-3 px-5 py-4', !notification.is_read && 'bg-accent-50/40')}
                  >
                    <span className={cn('mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl', visual.className)}>
                      {visual.icon}
                    </span>

                    <button
                      type="button"
                      className="min-w-0 flex-1 text-start"
                      onClick={() => {
                        if (!notification.is_read) markRead.mutate(notification.id);
                        if (link) navigate(link);
                      }}
                    >
                      <p className="text-sm font-semibold text-ink">{notification.title}</p>
                      <p className="mt-0.5 text-sm leading-relaxed text-ink-soft">{notification.body}</p>
                      <p className="mt-1 text-xs text-muted">{formatRelative(notification.created_at)}</p>
                    </button>

                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="حذف الإشعار"
                      onClick={() => remove.mutate(notification.id, { onError: (error) => toast.error(errorMessage(error)) })}
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </li>
                );
              })}
            </ul>
            <Pagination meta={query.data.meta} onPageChange={setPage} />
          </>
        ) : (
          <EmptyState
            title={filter === 'unread' ? 'لا توجد إشعارات غير مقروءة' : 'لا توجد إشعارات'}
            description="ستظهر هنا الإشعارات عند حدوث نشاط يخص حسابك."
          />
        )}
      </Card>
    </>
  );
}
