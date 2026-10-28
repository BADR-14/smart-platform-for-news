import { ChevronsLeft, ChevronsRight, LogOut, Menu, X } from 'lucide-react';
import { Suspense, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import logoMark from '@/assets/logo-mark.png';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Avatar } from '@/components/ui/Misc';
import { LoadingState } from '@/components/ui/States';
import { useAuth } from '@/features/auth/useAuth';
import { NotificationsMenu } from '@/features/notifications/components/NotificationsMenu';
import { cn } from '@/lib/utils/cn';
import { formatLongDate } from '@/lib/utils/format';
import type { NavSection } from './navigation';

interface DashboardLayoutProps {
  navigation: NavSection[];
  /** نص صغير تحت اسم المنصة يميّز اللوحة (لوحة الإدارة / لوحة الشركة). */
  areaLabel: string;
  basePath: string;
}

const COLLAPSED_KEY = 'smart-news.sidebar-collapsed';

/**
 * الهيكل المشترك للوحتي الأدمن والشركة: شريط جانبي + شريط علوي + محتوى الصفحة.
 * الشريط الجانبي ثابت على الشاشات الكبيرة ويمكن طيّه إلى أيقونات، ودرج منزلق على الموبايل.
 */
export function DashboardLayout({ navigation, areaLabel, basePath }: DashboardLayoutProps) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(() => localStorage.getItem(COLLAPSED_KEY) === '1');
  const [confirmLogout, setConfirmLogout] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const { account, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const allItems = navigation.flatMap((section) => section.items);
  // عنوان الصفحة الحالية في الشريط العلوي: أطول مسار يطابق العنوان الحالي
  const currentItem = allItems
    .filter((item) => (item.end ? location.pathname === item.to : location.pathname.startsWith(item.to)))
    .sort((a, b) => b.to.length - a.to.length)[0];

  const toggleCollapsed = () => {
    setCollapsed((value) => {
      localStorage.setItem(COLLAPSED_KEY, value ? '0' : '1');
      return !value;
    });
  };

  const handleLogout = async () => {
    setLoggingOut(true);
    await logout();
    navigate('/login', { replace: true });
  };

  const renderSidebar = (compact: boolean) => (
    <div className="bg-brand-gradient relative flex h-full flex-col overflow-hidden text-brand-100">
      <div aria-hidden className="bg-dots pointer-events-none absolute inset-0 opacity-60" />
      <div aria-hidden className="pointer-events-none absolute -end-24 -top-24 size-64 rounded-full bg-accent-500/20 blur-3xl" />

      <div className={cn('relative flex items-center gap-3 px-5 py-5', compact && 'justify-center px-0')}>
        <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-white p-1 shadow-lg shadow-black/20">
          <img src={logoMark} alt="" className="size-full" />
        </span>
        {!compact && (
          <div className="min-w-0 leading-tight">
            <p className="truncate text-sm font-bold text-white">Smart Platform</p>
            <p className="text-xs font-medium text-accent-300">{areaLabel}</p>
          </div>
        )}
      </div>

      <nav className={cn('relative flex-1 space-y-5 overflow-y-auto px-3 py-2', compact && 'px-2')} aria-label="القائمة الرئيسية">
        {navigation.map((section, index) => (
          <div key={section.title ?? index}>
            {section.title && !compact && (
              <p className="mb-1.5 px-3 text-[11px] font-semibold tracking-wide text-brand-300">{section.title}</p>
            )}
            {section.title && compact && <div className="mx-3 mb-2 border-t border-white/10" />}
            <ul className="space-y-1">
              {section.items.map((item) => (
                <li key={item.to}>
                  <NavLink
                    to={item.to}
                    end={item.end}
                    title={compact ? item.label : undefined}
                    // إغلاق درج الموبايل عند الانتقال لصفحة أخرى
                    onClick={() => setMobileOpen(false)}
                    className={({ isActive }) =>
                      cn(
                        'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                        compact && 'justify-center px-0',
                        isActive
                          ? 'bg-accent-gradient text-white shadow-(--shadow-glow)'
                          : 'text-brand-100 hover:bg-white/10 hover:text-white',
                      )
                    }
                  >
                    <span className="transition-transform duration-200 group-hover:scale-110">{item.icon}</span>
                    {!compact && item.label}
                  </NavLink>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </nav>

      <div className={cn('relative border-t border-white/10 p-3', compact && 'px-2')}>
        {account && !compact && (
          <div className="mb-2 flex items-center gap-3 rounded-xl bg-white/8 px-3 py-2.5">
            <Avatar name={account.name} src={account.avatar} size="sm" />
            <div className="min-w-0 leading-tight">
              <p className="truncate text-sm font-semibold text-white">{account.name}</p>
              <p className="ltr truncate text-end text-[11px] text-brand-200">{account.email}</p>
            </div>
          </div>
        )}
        <button
          type="button"
          title={compact ? 'تسجيل الخروج' : undefined}
          onClick={() => setConfirmLogout(true)}
          className={cn(
            'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-brand-100 transition-colors hover:bg-danger-600/90 hover:text-white',
            compact && 'justify-center px-0',
          )}
        >
          <LogOut className="size-5" />
          {!compact && 'تسجيل الخروج'}
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen">
      {/* الشريط الجانبي — شاشات كبيرة */}
      <aside
        className={cn(
          'fixed inset-y-0 start-0 z-30 hidden transition-[width] duration-300 ease-out lg:block',
          collapsed ? 'w-[4.75rem]' : 'w-64',
        )}
      >
        {renderSidebar(collapsed)}
      </aside>

      {/* الشريط الجانبي — موبايل */}
      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="animate-fade-in absolute inset-0 bg-brand-950/60 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
          <aside className="animate-slide-in absolute inset-y-0 start-0 w-64 shadow-(--shadow-pop)">
            {renderSidebar(false)}
            <button
              type="button"
              onClick={() => setMobileOpen(false)}
              aria-label="إغلاق القائمة"
              className="absolute end-3 top-5 rounded-lg p-1 text-brand-200 hover:bg-white/10 hover:text-white"
            >
              <X className="size-5" />
            </button>
          </aside>
        </div>
      )}

      <div className={cn('transition-[padding] duration-300 ease-out', collapsed ? 'lg:ps-[4.75rem]' : 'lg:ps-64')}>
        <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line/80 bg-surface/80 px-4 backdrop-blur-xl sm:px-6">
          <button
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label="فتح القائمة"
            className="flex size-10 items-center justify-center rounded-xl text-ink-soft hover:bg-brand-50 lg:hidden"
          >
            <Menu className="size-5" />
          </button>

          <button
            type="button"
            onClick={toggleCollapsed}
            aria-label={collapsed ? 'توسيع القائمة' : 'طيّ القائمة'}
            className="hidden size-10 items-center justify-center rounded-xl text-ink-soft transition-colors hover:bg-brand-50 hover:text-brand-800 lg:flex"
          >
            {/* في العربية القائمة على اليمين: السهم لليمين يطويها */}
            {collapsed ? <ChevronsLeft className="size-5" /> : <ChevronsRight className="size-5" />}
          </button>

          <div className="min-w-0 flex-1 leading-tight">
            <p className="truncate text-sm font-bold text-ink">{currentItem?.label ?? areaLabel}</p>
            <p className="hidden truncate text-xs text-muted sm:block">{formatLongDate(new Date())}</p>
          </div>

          <div className="flex items-center gap-2">
            <NotificationsMenu allPath={`${basePath}/notifications`} />

            {account && (
              <div className="flex items-center gap-2.5 rounded-full border border-line bg-surface py-1 pe-1 ps-3.5 shadow-sm">
                <p className="hidden max-w-36 truncate text-sm font-semibold text-ink sm:block">{account.name}</p>
                <Avatar name={account.name} src={account.avatar} size="sm" />
              </div>
            )}
          </div>
        </header>

        <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
          <Suspense fallback={<LoadingState />}>
            {/* المفتاح يعيد تشغيل حركة الدخول عند كل انتقال بين الصفحات */}
            <div key={location.pathname} className="animate-rise">
              <Outlet />
            </div>
          </Suspense>
        </main>
      </div>

      <ConfirmDialog
        open={confirmLogout}
        onClose={() => setConfirmLogout(false)}
        onConfirm={handleLogout}
        loading={loggingOut}
        tone="primary"
        title="تسجيل الخروج"
        message="هل تريد تسجيل الخروج من لوحة التحكم؟"
        confirmLabel="تسجيل الخروج"
      />
    </div>
  );
}
