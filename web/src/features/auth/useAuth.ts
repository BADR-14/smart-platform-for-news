import { useContext } from 'react';
import { isAdminSite } from '@/config/env';
import type { Account } from '@/features/shared/types';
import { AuthContext } from './auth-context';
import type { AuthContextValue } from './auth-context';

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used inside <AuthProvider>');
  }

  return context;
}

/**
 * الصفحة التي يذهب إليها الحساب بعد الدخول، حسب الموقع:
 *  - موقع الإدارة: لوحة الإدارة.
 *  - موقع الشركات: لوحة الشركة إن كان الحساب شركة، وإلا صفحة طلب إنشاء الشركة.
 * حساب لا يخص الموقع (أدمن في موقع الشركات مثلاً) يذهب لصفحة توضّح ذلك.
 */
export function homePathFor(account: Account): string {
  if (isAdminSite) {
    return account.type === 'admin' ? '/admin' : '/wrong-site';
  }

  switch (account.type) {
    case 'company':
      return '/company';
    case 'user':
      return '/onboarding';
    default:
      return '/wrong-site';
  }
}
