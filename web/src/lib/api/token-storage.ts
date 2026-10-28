import { env } from '@/config/env';

// مفتاح مختلف لكل موقع: جلسة موقع الإدارة لا تختلط بجلسة موقع الشركات
const KEY = `smart-news.${env.target}.token`;

/**
 * تخزين توكن الدخول.
 * "تذكّرني" ⟵ localStorage (يبقى بعد إغلاق المتصفح)، وبدونها ⟵ sessionStorage (ينتهي بإغلاق التبويب).
 */
export const tokenStorage = {
  get(): string | null {
    return sessionStorage.getItem(KEY) ?? localStorage.getItem(KEY);
  },

  set(token: string, remember: boolean): void {
    this.clear();
    (remember ? localStorage : sessionStorage).setItem(KEY, token);
  },

  clear(): void {
    sessionStorage.removeItem(KEY);
    localStorage.removeItem(KEY);
  },
};
