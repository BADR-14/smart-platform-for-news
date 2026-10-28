import { api } from '@/lib/api/client';
import type { ApiResponse, MessageResponse } from '@/lib/api/types';
import type { TokenPayload } from './api';

/**
 * دخول موقع الإدارة: رابط سحري بدون كلمة مرور.
 * في ملف مستقل حتى لا تدخل مسارات دخول الإدارة في ملفات موقع الشركات.
 */
export const adminAuthApi = {
  async requestMagicLink(email: string): Promise<string> {
    const { data } = await api.post<MessageResponse>('/admin/auth/magic-link', { email });
    return data.message;
  },

  async verifyMagicLink(email: string, token: string): Promise<TokenPayload> {
    const { data } = await api.post<ApiResponse<TokenPayload>>('/admin/auth/magic-link/verify', {
      email,
      token,
      device_name: 'admin-dashboard',
    });
    return data.data;
  },
};
