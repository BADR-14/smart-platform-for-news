import axios from 'axios';
import { env } from '@/config/env';
import { toApiError } from './errors';
import { tokenStorage } from './token-storage';

/**
 * نسخة Axios الوحيدة في التطبيق.
 *  - تضيف توكن الدخول لكل طلب.
 *  - تحوّل كل الأخطاء إلى ApiError.
 *  - عند 401 (توكن منتهٍ أو ملغى) تبلّغ طبقة المصادقة لتسجيل الخروج.
 */
export const api = axios.create({
  baseURL: env.apiUrl,
  headers: { Accept: 'application/json' },
  timeout: 30_000,
});

type UnauthorizedHandler = () => void;
let onUnauthorized: UnauthorizedHandler | null = null;

export function setUnauthorizedHandler(handler: UnauthorizedHandler | null): void {
  onUnauthorized = handler;
}

api.interceptors.request.use((config) => {
  const token = tokenStorage.get();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    const apiError = toApiError(error);

    // طلب الدخول نفسه يرجع 401 عند خطأ كلمة المرور، وهذا ليس انتهاء جلسة
    const url = axios.isAxiosError(error) ? (error.config?.url ?? '') : '';
    const isLoginRequest = ['/login', '/verify-otp', '/admin/auth/'].some((path) => url.includes(path));

    if (apiError.status === 401 && !isLoginRequest) {
      onUnauthorized?.();
    }

    return Promise.reject(apiError);
  },
);
