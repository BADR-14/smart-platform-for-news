/** أي الموقعين يعمل الآن: موقع الشركات أم موقع الإدارة (يُحدَّد وقت البناء بالـ mode). */
export type AppTarget = 'admin' | 'company';

/**
 * إعدادات البيئة — المصدر الوحيد لقيم import.meta.env داخل التطبيق.
 */
export const env = {
  apiUrl: (import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api').replace(/\/+$/, ''),
  target: (import.meta.env.MODE === 'admin' ? 'admin' : 'company') as AppTarget,
} as const;

export const isAdminSite = env.target === 'admin';
