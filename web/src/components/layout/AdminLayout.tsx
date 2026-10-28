import { useQuery } from '@tanstack/react-query';
import { ChevronLeft, LogOut, Menu, Search, ShieldCheck, X } from 'lucide-react';
import { Suspense, useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import logoMark from '@/assets/logo-mark.png';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Avatar } from '@/components/ui/Misc';
import { LoadingState } from '@/components/ui/States';
import { adminApi } from '@/features/admin/api';
import { adminKeys } from '@/features/admin/keys';
import { useAuth } from '@/features/auth/useAuth';
import { NotificationsMenu } from '@/features/notifications/components/NotificationsMenu';
import { cn } from '@/lib/utils/cn';
import { formatLongDate, formatNumber } from '@/lib/utils/format';
import { adminNavigation } from './admin-navigation';
import { CommandPalette } from './CommandPalette';

const BASE_PATH = '/admin';
const allItems = adminNavigation.flatMap((section) => section.items);

/**
 * هيكل لوحة الإدارة — مستقل عن هيكل لوحة الشركة شكلاً وبنيةً:
 * شريط جانبي فاتح بعدّادات لما ينتظر المراجعة، وشريط علوي فيه مسار الصفحة
 * ولوحة أوامر (Ctrl+K) ومؤشر حالة الاتصال بالخادم.
 */
export function AdminLayout() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [paletteOpen, setPaletteOpen] = useState(false);
  const [confirmLogout, setConfirmLogout] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const { account, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // نفس استعلام الصفحة الرئيسية: يغذّي عدّادات القائمة ومؤشر الحالة دون طلب إضافي
  const overview = useQuery({ queryKey: adminKeys.overview, queryFn: adminApi.overview, staleTime: 60_000 });
  const stats = overview.data?.statistics;

  const badges: Record<string, number | undefined> = {
    '/admin/verification': stats?.pending_verifications,
    '/admin/reports': stats?.pending_reports,
    '/admin/posts': stats?.under_review_posts,
  };

  const currentItem = allItems
    .filter((item) => (item.end ? location.pathname === item.to : location.pathname.startsWith(item.to)))
    .sort((a, b) => b.to.length - a.to.length)[0];

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setPaletteOpen((value) => !value);
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  const handleLogout = async () => {
    setLoggingOut(true);
    await logout();
    navigate('/login', { replace: true });
  };

  const sidebar = (
    <div className="flex h-full flex-col border-e border-line bg-surface">
      <Link to={BASE_PATH} className="flex h-16 shrink-0 items-center gap-3 border-b border-line px-5">
        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-graphite-900 p-1">
          <img src={logoMark} alt="" className="size-full rounded-lg bg-white" />
        </span>
        <span className="min-w-0 leading-tight">
          <span className="block truncate text-sm font-bold text-ink">Smart Platform</span>
          <span className="flex items-center gap-1 text-[11px] font-semibold text-accent-700">
            <ShieldCheck className="size-3" />
            Admin Console
          </span>
        </span>
      </Link>

      <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="القائمة الرئيسية">
        {adminNavigation.map((section, index) => (
          <div key={section.title ?? index}>
            {section.title && <p className="mb-2 px-3 text-[11px] font-bold tracking-wider text-graphite-400">{section.title}</p>}
            <ul className="space-y-0.5">
              {section.items.map((item) => {
                const badge = badges[item.to] ?? 0;

                return (
                  <li key={item.to}>
                    <NavLink
                      to={item.to}
                      end={item.end}
                      onClick={() => setMobileOpen(false)}
                      className={({ isActive }) =>
                        cn(
                          'group relative flex items-center gap-3 rounded-lg px-3 py-2.25 text-sm font-medium transition-colors duration-150',
                          isActive ? 'bg-graphite-900 text-white shadow-sm' : 'text-graphite-600 hover:bg-graphite-50 hover:text-ink',
                        )
                      }
                    >
                      {({ isActive }) => (
                        <>
                          {/* شريط برتقالي يعلّم الصفحة الحالية */}
                          {isActive && <span aria-hidden className="absolute inset-y-2 -start-3 w-1 rounded-e-full bg-accent-500" />}
                          <span className={cn('transition-colors', isActive ? 'text-accent-400' : 'text-graphite-400 group-hover:text-graphite-700')}>
                            {item.icon}
                          </span>
                          <span className="flex-1 truncate">{item.label}</span>
                          {badge > 0 && (
                            <span
                              className={cn(
                                'ltr min-w-5.5 rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums',
                                isActive ? 'bg-accent-500 text-white' : 'bg-accent-50 text-accent-700',
                              )}
                            >
                              {formatNumber(badge)}
                            </span>
                          )}
                        </>
                      )}
                    </NavLink>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
      </nav>

      <div className="border-t border-line p-3">
        {account && (
          <div className="flex items-center gap-3 rounded-xl bg-graphite-50 px-3 py-2.5">
            <Avatar name={account.name} src={account.avatar} size="sm" />
            <div className="min-w-0 flex-1 leading-tight">
              <p className="truncate text-sm font-semibold text-ink">{account.name}</p>
              <p className="text-[11px] text-muted">مدير المنصة</p>
            </div>
            <button
              type="button"
              title="تسجيل الخروج"
              aria-label="تسجيل الخروج"
              onClick={() => setConfirmLogout(true)}
              className="flex size-9 items-center justify-center rounded-lg text-graphite-500 transition-colors hover:bg-danger-50 hover:text-danger-600"
            >
              <LogOut className="size-4.5" />
            </button>
          </div>
        )}
      </div>
    </div>
  );

  return (
    <div className="min-h-screen">
      {/* خط الهوية أعلى الصفحة */}
      <div aria-hidden className="fixed inset-x-0 top-0 z-40 h-0.75 bg-linear-to-l from-accent-500 via-accent-400 to-graphite-900" />

      <aside className="fixed inset-y-0 start-0 z-30 hidden w-60 pt-0.75 lg:block">{sidebar}</aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="animate-fade-in absolute inset-0 bg-graphite-950/55 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
          <aside className="animate-slide-in absolute inset-y-0 start-0 w-64 shadow-(--shadow-pop)">
            {sidebar}
            <button
              type="button"
              onClick={() => setMobileOpen(false)}
              aria-label="إغلاق القائمة"
              className="absolute end-3 top-4 rounded-lg p-1.5 text-graphite-500 hover:bg-graphite-100"
            >
              <X className="size-5" />
            </button>
          </aside>
        </div>
      )}

      <div className="pt-0.75 lg:ps-60">
        <header className="sticky top-0.75 z-20 flex h-16 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur-xl sm:px-6">
          <button
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label="فتح القائمة"
            className="flex size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-graphite-50 lg:hidden"
          >
            <Menu className="size-5" />
          </button>

          {/* مسار الصفحة */}
          <nav aria-label="مسار الصفحة" className="flex min-w-0 items-center gap-1.5 text-sm">
            <Link to={BASE_PATH} className="hidden shrink-0 font-medium text-muted hover:text-ink sm:block">
              لوحة الإدارة
            </Link>
            <ChevronLeft className="hidden size-4 shrink-0 text-graphite-300 sm:block" />
            <span className="truncate font-bold text-ink">{currentItem?.label ?? 'لوحة الإدارة'}</span>
          </nav>

          <button
            type="button"
            onClick={() => setPaletteOpen(true)}
            className="ms-auto hidden h-10 w-72 items-center gap-2.5 rounded-lg border border-line bg-canvas px-3 text-sm text-muted transition-colors hover:border-line-strong hover:bg-surface md:flex"
          >
            <Search className="size-4" />
            <span className="flex-1 text-start">انتقال سريع…</span>
            <kbd className="ltr rounded-md border border-line bg-surface px-1.5 py-0.5 text-[11px] font-medium">Ctrl K</kbd>
          </button>

          <div className="ms-auto flex items-center gap-2 md:ms-0">
            <ServerStatus loading={overview.isLoading} failed={overview.isError} />

            <button
              type="button"
              onClick={() => setPaletteOpen(true)}
              aria-label="انتقال سريع"
              className="flex size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-graphite-50 md:hidden"
            >
              <Search className="size-5" />
            </button>

            <NotificationsMenu allPath={`${BASE_PATH}/notifications`} />
          </div>
        </header>

        <main className="mx-auto w-full max-w-[88rem] px-4 py-6 sm:px-6 lg:py-7">
          <Suspense fallback={<LoadingState />}>
            <div key={location.pathname} className="animate-rise">
              <Outlet />
            </div>
          </Suspense>
        </main>

        <footer className="flex flex-wrap items-center justify-between gap-2 border-t border-line px-6 py-4 text-xs text-muted">
          <span>Smart Platform for News — Admin Console</span>
          <span>{formatLongDate(new Date())}</span>
        </footer>
      </div>

      <CommandPalette open={paletteOpen} onClose={() => setPaletteOpen(false)} items={allItems} />

      <ConfirmDialog
        open={confirmLogout}
        onClose={() => setConfirmLogout(false)}
        onConfirm={handleLogout}
        loading={loggingOut}
        tone="primary"
        title="تسجيل الخروج"
        message="هل تريد تسجيل الخروج من لوحة الإدارة؟"
        confirmLabel="تسجيل الخروج"
      />
    </div>
  );
}

/** حالة الاتصال بالخادم، مأخوذة من نتيجة آخر طلب فعلي للوحة. */
function ServerStatus({ loading, failed }: { loading: boolean; failed: boolean }) {
  const label = loading ? 'جارٍ الاتصال' : failed ? 'تعذّر الاتصال' : 'الخادم متصل';
  const dot = loading ? 'bg-warning-600' : failed ? 'bg-danger-600' : 'bg-success-600';

  return (
    <span className="hidden items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink-soft sm:flex">
      <span className="relative flex size-2">
        {!failed && <span className={cn('absolute inline-flex size-full animate-ping rounded-full opacity-60', dot)} />}
        <span className={cn('relative inline-flex size-2 rounded-full', dot)} />
      </span>
      {label}
    </span>
  );
}
