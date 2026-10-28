import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { EmptyState, LoadingState } from '@/components/ui/States';
import { useAuth } from '@/features/auth/useAuth';
import { cn } from '@/lib/utils/cn';
import { formatRelative } from '@/lib/utils/format';
import type { AppNotification } from '../api';
import { useNotificationActions, useNotifications, useUnreadCount } from '../hooks';
import { notificationLink, notificationVisual } from '../notification-meta';

interface NotificationsMenuProps {
  /** مسار صفحة "كل الإشعارات" في اللوحة الحالية. */
  allPath: string;
}

export function NotificationsMenu({ allPath }: NotificationsMenuProps) {
  const [open, setOpen] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);
  const navigate = useNavigate();
  const { account } = useAuth();

  const { data: unread = 0 } = useUnreadCount();
  // القائمة تُجلب عند فتح القائمة المنسدلة فقط؛ العدّاد وحده يُحدَّث دورياً
  const list = useNotifications(1, { perPage: 8, enabled: open });
  const { markRead, markAllRead } = useNotificationActions();

  useEffect(() => {
    if (!open) return;

    const onPointerDown = (event: MouseEvent) => {
      if (!wrapperRef.current?.contains(event.target as Node)) setOpen(false);
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpen(false);
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);

    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  const handleOpen = (notification: AppNotification) => {
    if (!notification.is_read) markRead.mutate(notification.id);

    const link = account ? notificationLink(notification, account.type) : null;

    if (link) {
      setOpen(false);
      navigate(link);
    }
  };

  return (
    <div ref={wrapperRef} className="relative">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        aria-label={`الإشعارات${unread > 0 ? ` (${unread} غير مقروء)` : ''}`}
        aria-expanded={open}
        className="relative flex size-10 items-center justify-center rounded-xl text-ink-soft transition-colors hover:bg-brand-50 hover:text-brand-800"
      >
        <Bell className="size-5" />
        {unread > 0 && (
          <span className="absolute end-1 top-1 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white ring-2 ring-surface">
            {unread > 99 ? '99+' : unread}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute end-0 z-40 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-surface shadow-(--shadow-pop) animate-pop-in">
          <div className="flex items-center justify-between border-b border-line px-4 py-3">
            <div>
              <p className="font-semibold text-ink">الإشعارات</p>
              <p className="text-xs text-muted">{unread > 0 ? `${unread} غير مقروء` : 'لا يوجد جديد'}</p>
            </div>
            {unread > 0 && (
              <button
                type="button"
                onClick={() => markAllRead.mutate()}
                className="text-xs font-medium text-accent-700 hover:underline"
              >
                تحديد الكل كمقروء
              </button>
            )}
          </div>

          <div className="max-h-96 overflow-y-auto">
            {list.isLoading ? (
              <LoadingState className="py-10" />
            ) : list.data?.data.length ? (
              list.data.data.map((notification) => {
                const visual = notificationVisual(notification.type);

                return (
                  <button
                    key={notification.id}
                    type="button"
                    onClick={() => handleOpen(notification)}
                    className={cn(
                      'flex w-full items-start gap-3 border-b border-line px-4 py-3 text-start transition-colors last:border-b-0 hover:bg-brand-50/60',
                      !notification.is_read && 'bg-accent-50/50',
                    )}
                  >
                    <span className={cn('mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl', visual.className)}>
                      {visual.icon}
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm font-medium text-ink">{notification.title}</span>
                      <span className="mt-0.5 line-clamp-2 block text-xs leading-relaxed text-muted">{notification.body}</span>
                      <span className="mt-1 block text-[11px] text-muted/80">{formatRelative(notification.created_at)}</span>
                    </span>
                    {!notification.is_read && <span className="mt-2 size-2 shrink-0 rounded-full bg-accent-500" />}
                  </button>
                );
              })
            ) : (
              <EmptyState title="لا توجد إشعارات" className="py-10" />
            )}
          </div>

          <Link
            to={allPath}
            onClick={() => setOpen(false)}
            className="block border-t border-line px-4 py-3 text-center text-sm font-medium text-brand-700 hover:bg-brand-50"
          >
            عرض كل الإشعارات
          </Link>
        </div>
      )}
    </div>
  );
}
