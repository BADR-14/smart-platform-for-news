import { api } from '@/lib/api/client';
import type { ApiResponse, PageParams, Paginated } from '@/lib/api/types';

export interface AppNotification {
  id: string;
  type: string;
  title: string | null;
  body: string | null;
  meta: Record<string, unknown>;
  is_read: boolean;
  created_at: string | null;
  time_ago: string | null;
}

export interface NotificationPreferences {
  email_notifications: boolean;
  login_notifications: boolean;
}

type NotificationsPage = Paginated<AppNotification, { unread_count: number }>;

export const notificationsApi = {
  async list(params: PageParams & { unread_only?: boolean }): Promise<NotificationsPage> {
    const { data } = await api.get<NotificationsPage>('/notifications', {
      params: { ...params, unread_only: params.unread_only ? 1 : undefined },
    });
    return data;
  },

  async unreadCount(): Promise<number> {
    const { data } = await api.get<ApiResponse<{ unread_count: number }>>('/notifications/unread-count');
    return data.data.unread_count;
  },

  async markRead(id: string): Promise<void> {
    await api.patch(`/notifications/${id}/read`);
  },

  async markAllRead(): Promise<void> {
    await api.patch('/notifications/read-all');
  },

  async remove(id: string): Promise<void> {
    await api.delete(`/notifications/${id}`);
  },

  async getPreferences(): Promise<NotificationPreferences> {
    const { data } = await api.get<ApiResponse<NotificationPreferences>>('/notification-settings');
    return data.data;
  },

  async updatePreferences(input: NotificationPreferences): Promise<NotificationPreferences> {
    const { data } = await api.patch<ApiResponse<NotificationPreferences>>('/notification-settings', input);
    return data.data;
  },
};
