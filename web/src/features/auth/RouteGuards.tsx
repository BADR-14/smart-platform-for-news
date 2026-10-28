import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState } from '@/components/ui/States';
import type { AccountType } from '@/features/shared/types';
import { homePathFor, useAuth } from './useAuth';

/**
 * يحمي مجموعة مسارات: يجب أن يكون المستخدم مسجلاً ومن نوع الحساب المطلوب.
 * الحماية الحقيقية في الباك-إند؛ هذا الحارس يمنع فقط عرض واجهة لا تخص المستخدم.
 */
export function RequireRole({ role }: { role: AccountType }) {
  const { status, account } = useAuth();
  const location = useLocation();

  if (status === 'loading') {
    return <LoadingState className="min-h-screen" />;
  }

  if (status === 'guest' || !account) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (account.type !== role) {
    return <Navigate to={homePathFor(account)} replace />;
  }

  return <Outlet />;
}

/** صفحات تحتاج تسجيل دخول فقط، أياً كان نوع الحساب (مثل صفحة "حساب لا يخص هذا الموقع"). */
export function RequireAuth() {
  const { status, account } = useAuth();

  if (status === 'loading') {
    return <LoadingState className="min-h-screen" />;
  }

  if (status === 'guest' || !account) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}

/** صفحات الزائر (الدخول ...): المسجَّل دخوله يُحوَّل مباشرة إلى لوحته. */
export function GuestOnly() {
  const { status, account } = useAuth();

  if (status === 'loading') {
    return <LoadingState className="min-h-screen" />;
  }

  if (status === 'authenticated' && account) {
    return <Navigate to={homePathFor(account)} replace />;
  }

  return <Outlet />;
}
