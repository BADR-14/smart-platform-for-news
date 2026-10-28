import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationsApi } from './api';

export const notificationKeys = {
  all: ['notifications'] as const,
  list: (page: number, unreadOnly: boolean, perPage: number) => ['notifications', 'list', page, unreadOnly, perPage] as const,
  unreadCount: ['notifications', 'unread-count'] as const,
  preferences: ['notifications', 'preferences'] as const,
};

/** عدّاد غير المقروء — يُحدَّث كل 30 ثانية (بديل بسيط عن WebSocket). */
export function useUnreadCount() {
  return useQuery({
    queryKey: notificationKeys.unreadCount,
    queryFn: notificationsApi.unreadCount,
    refetchInterval: 30_000,
  });
}

interface NotificationsOptions {
  unreadOnly?: boolean;
  perPage?: number;
  enabled?: boolean;
}

export function useNotifications(page: number, { unreadOnly = false, perPage = 15, enabled = true }: NotificationsOptions = {}) {
  return useQuery({
    queryKey: notificationKeys.list(page, unreadOnly, perPage),
    queryFn: () => notificationsApi.list({ page, per_page: perPage, unread_only: unreadOnly }),
    placeholderData: keepPreviousData,
    enabled,
  });
}

export function useNotificationActions() {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: notificationKeys.all });

  return {
    markRead: useMutation({ mutationFn: notificationsApi.markRead, onSuccess: invalidate }),
    markAllRead: useMutation({ mutationFn: notificationsApi.markAllRead, onSuccess: invalidate }),
    remove: useMutation({ mutationFn: notificationsApi.remove, onSuccess: invalidate }),
  };
}
