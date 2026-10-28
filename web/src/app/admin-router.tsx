import { createBrowserRouter, Navigate } from 'react-router-dom';
import { AdminLayout } from '@/components/layout/AdminLayout';
import { AdminLoginPage } from '@/features/auth/pages/admin/AdminLoginPage';
import { AdminVerifyPage } from '@/features/auth/pages/admin/AdminVerifyPage';
import { WrongSitePage } from '@/features/auth/pages/WrongSitePage';
import { GuestOnly, RequireAuth, RequireRole } from '@/features/auth/RouteGuards';
import { NotFoundPage } from './NotFoundPage';

/**
 * مسارات موقع الإدارة: دخول بالرابط السحري ثم لوحة الإدارة.
 * موقع مستقل عن موقع الشركات: لا صفحة تعريفية ولا تسجيل ولا كلمات مرور.
 */
export const router = createBrowserRouter([
  { path: '/', element: <Navigate to="/admin" replace /> },

  {
    element: <GuestOnly />,
    children: [{ path: '/login', element: <AdminLoginPage /> }],
  },

  // الرابط السحري يعمل حتى لو كانت هناك جلسة سابقة مفتوحة
  { path: '/auth/verify', element: <AdminVerifyPage /> },

  {
    element: <RequireAuth />,
    children: [{ path: '/wrong-site', element: <WrongSitePage /> }],
  },

  {
    element: <RequireRole role="admin" />,
    children: [
      {
        path: '/admin',
        element: <AdminLayout />,
        children: [
          { index: true, lazy: () => import('@/features/admin/overview/AdminOverviewPage').then((m) => ({ Component: m.AdminOverviewPage })) },
          { path: 'users', lazy: () => import('@/features/admin/users/UsersPage').then((m) => ({ Component: m.UsersPage })) },
          { path: 'companies', lazy: () => import('@/features/admin/companies/CompaniesPage').then((m) => ({ Component: m.CompaniesPage })) },
          { path: 'posts', lazy: () => import('@/features/admin/posts/PostsPage').then((m) => ({ Component: m.PostsPage })) },
          { path: 'reports', lazy: () => import('@/features/admin/reports/ReportsPage').then((m) => ({ Component: m.ReportsPage })) },
          { path: 'verification', lazy: () => import('@/features/admin/verification/VerificationPage').then((m) => ({ Component: m.VerificationPage })) },
          { path: 'plans', lazy: () => import('@/features/admin/plans/PlansPage').then((m) => ({ Component: m.PlansPage })) },
          { path: 'categories', lazy: () => import('@/features/admin/categories/CategoriesPage').then((m) => ({ Component: m.CategoriesPage })) },
          { path: 'analytics', lazy: () => import('@/features/admin/analytics/AnalyticsPage').then((m) => ({ Component: m.AnalyticsPage })) },
          { path: 'notifications', lazy: () => import('@/features/notifications/pages/NotificationsPage').then((m) => ({ Component: m.NotificationsPage })) },
          { path: 'settings', lazy: () => import('@/features/settings/SettingsPage').then((m) => ({ Component: m.SettingsPage })) },
        ],
      },
    ],
  },

  { path: '*', element: <NotFoundPage /> },
]);
