import { createBrowserRouter } from 'react-router-dom';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { companyNavigation } from '@/components/layout/company-navigation';
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage';
import { LoginPage } from '@/features/auth/pages/LoginPage';
import { RegisterPage } from '@/features/auth/pages/RegisterPage';
import { ResetPasswordPage } from '@/features/auth/pages/ResetPasswordPage';
import { WrongSitePage } from '@/features/auth/pages/WrongSitePage';
import { GuestOnly, RequireAuth, RequireRole } from '@/features/auth/RouteGuards';
import { LandingPage } from '@/features/landing/LandingPage';
import { OnboardingPage } from '@/features/onboarding/OnboardingPage';
import { NotFoundPage } from './NotFoundPage';

/**
 * مسارات موقع الشركات: الصفحة التعريفية، الدخول، تسجيل شركة جديدة، ولوحة الشركة.
 * صفحات اللوحة تُحمَّل عند الطلب (lazy). لا يوجد في هذا الموقع أي مسار أو كود للإدارة.
 */
export const router = createBrowserRouter([
  { path: '/', element: <LandingPage /> },

  {
    element: <GuestOnly />,
    children: [
      { path: '/login', element: <LoginPage /> },
      { path: '/register', element: <RegisterPage /> },
      { path: '/forgot-password', element: <ForgotPasswordPage /> },
    ],
  },

  // رابط البريد يجب أن يعمل حتى لو كان المستخدم مسجلاً دخوله بحساب آخر
  { path: '/reset-password', element: <ResetPasswordPage /> },

  // حساب مسجَّل لكنه ليس شركة بعد: يكمل طلب إنشاء الشركة أو ينتظر المراجعة
  {
    element: <RequireRole role="user" />,
    children: [{ path: '/onboarding', element: <OnboardingPage /> }],
  },

  {
    element: <RequireAuth />,
    children: [{ path: '/wrong-site', element: <WrongSitePage /> }],
  },

  {
    element: <RequireRole role="company" />,
    children: [
      {
        path: '/company',
        element: <DashboardLayout navigation={companyNavigation} areaLabel="لوحة الشركة" basePath="/company" />,
        children: [
          { index: true, lazy: () => import('@/features/company/overview/CompanyOverviewPage').then((m) => ({ Component: m.CompanyOverviewPage })) },
          { path: 'posts', lazy: () => import('@/features/company/posts/CompanyPostsPage').then((m) => ({ Component: m.CompanyPostsPage })) },
          { path: 'posts/new', lazy: () => import('@/features/company/posts/PostFormPage').then((m) => ({ Component: m.PostFormPage })) },
          { path: 'posts/:postId/edit', lazy: () => import('@/features/company/posts/PostFormPage').then((m) => ({ Component: m.PostFormPage })) },
          { path: 'featured', lazy: () => import('@/features/company/featured/FeaturedPage').then((m) => ({ Component: m.FeaturedPage })) },
          { path: 'wallet', lazy: () => import('@/features/company/wallet/WalletPage').then((m) => ({ Component: m.WalletPage })) },
          { path: 'plans', lazy: () => import('@/features/company/plans/CompanyPlansPage').then((m) => ({ Component: m.CompanyPlansPage })) },
          { path: 'profile', lazy: () => import('@/features/company/profile/CompanyProfilePage').then((m) => ({ Component: m.CompanyProfilePage })) },
          { path: 'notifications', lazy: () => import('@/features/notifications/pages/NotificationsPage').then((m) => ({ Component: m.NotificationsPage })) },
          { path: 'settings', lazy: () => import('@/features/settings/SettingsPage').then((m) => ({ Component: m.SettingsPage })) },
        ],
      },
    ],
  },

  { path: '*', element: <NotFoundPage /> },
]);
