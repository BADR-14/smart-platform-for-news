import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Clock, LogOut, RefreshCw, ShieldX } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import logoMark from '@/assets/logo-mark.png';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { ErrorState, LoadingState } from '@/components/ui/States';
import { Stepper } from '@/components/ui/Stepper';
import { authApi } from '@/features/auth/api';
import type { VerificationOverview } from '@/features/auth/api';
import { REGISTRATION_STEPS } from '@/features/auth/registration-steps';
import { useAuth } from '@/features/auth/useAuth';
import { formatDate } from '@/lib/utils/format';
import { CompanyRequestForm } from './CompanyRequestForm';

const STATUS_KEY = ['onboarding', 'verification-status'] as const;

/**
 * صفحة حساب مسجَّل في موقع الشركات لكنه ليس شركة بعد:
 *  - لم يقدّم طلباً (أو رُفض طلبه وله محاولات): نموذج بيانات الشركة.
 *  - طلبه قيد المراجعة: حالة الانتظار.
 *  - وافقت الإدارة: يتحول الحساب إلى شركة وننتقل للوحة.
 */
export function OnboardingPage() {
  const { account, logout, refreshAccount } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [checking, setChecking] = useState(false);

  const status = useQuery({ queryKey: STATUS_KEY, queryFn: authApi.verificationStatus });
  const isCompanyNow = status.data?.account_type === 'company';

  // وافقت الإدارة أثناء فتح الصفحة: نحدّث الجلسة وندخل اللوحة
  useEffect(() => {
    if (!isCompanyNow) return;

    void refreshAccount().then(() => navigate('/company', { replace: true }));
  }, [isCompanyNow, refreshAccount, navigate]);

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const recheck = async () => {
    setChecking(true);
    const result = await status.refetch();
    setChecking(false);

    if (result.data?.company.status === 'pending') toast.info('طلبك ما زال قيد المراجعة.');
  };

  const company = status.data?.company;
  const step = company?.status === 'pending' ? 3 : 2;

  return (
    <div className="min-h-screen">
      <header className="border-b border-line bg-surface/90 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-4xl items-center justify-between gap-3 px-4 sm:px-6">
          <div className="flex items-center gap-3">
            <img src={logoMark} alt="" className="size-10" />
            <div className="leading-tight">
              <p className="text-sm font-bold text-brand-800">Smart Platform</p>
              <p className="text-xs text-muted">تسجيل شركة جديدة</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <span className="ltr hidden text-xs text-muted sm:block">{account?.email}</span>
            <Button variant="ghost" size="sm" icon={<LogOut className="size-4" />} onClick={handleLogout}>
              خروج
            </Button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <Stepper steps={REGISTRATION_STEPS} current={step} className="mx-auto mb-8 max-w-xl" />

        {status.isLoading ? (
          <LoadingState />
        ) : status.error || !company ? (
          <ErrorState error={status.error} onRetry={() => status.refetch()} />
        ) : isCompanyNow ? (
          <LoadingState label="تمت الموافقة. جارٍ فتح لوحة شركتك…" />
        ) : company.status === 'pending' ? (
          <PendingCard submittedAt={company.submitted_at} checking={checking} onRecheck={recheck} />
        ) : company.can_submit ? (
          <div className="animate-rise">
            {company.status === 'rejected' && (
              <Alert tone="danger" title="رُفض طلبك السابق" className="mb-5">
                {company.rejection_reasons.length > 0 ? (
                  <ul className="mt-1 list-inside list-disc space-y-0.5">
                    {company.rejection_reasons.map((reason) => (
                      <li key={reason}>{reason}</li>
                    ))}
                  </ul>
                ) : (
                  'تأكد من وضوح الوثائق وصحة البيانات ثم أعد التقديم.'
                )}
                <p className="mt-2 font-medium">المحاولات المتبقية: {company.attempts_left}</p>
              </Alert>
            )}

            <CompanyRequestForm
              defaultName={account?.name ?? ''}
              onSubmitted={(overview: VerificationOverview) => {
                queryClient.setQueryData(STATUS_KEY, overview);
                toast.success('استلمنا طلبك. ستصلك النتيجة قريباً.');
                window.scrollTo({ top: 0, behavior: 'smooth' });
              }}
            />
          </div>
        ) : (
          <div className="animate-rise rounded-(--radius-card) border border-line bg-surface p-8 text-center shadow-(--shadow-card)">
            <span className="mx-auto flex size-16 items-center justify-center rounded-2xl bg-danger-50 text-danger-600">
              <ShieldX className="size-8" />
            </span>
            <h1 className="mt-4 text-xl font-bold text-ink">استنفدت محاولات التقديم</h1>
            <p className="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted">
              رُفض طلب إنشاء الشركة ولم تبقَ محاولات متاحة. تواصل مع إدارة المنصة لمراجعة حالتك.
            </p>
          </div>
        )}
      </main>
    </div>
  );
}

interface PendingCardProps {
  submittedAt: string | null;
  checking: boolean;
  onRecheck: () => void;
}

function PendingCard({ submittedAt, checking, onRecheck }: PendingCardProps) {
  return (
    <div className="animate-rise overflow-hidden rounded-(--radius-card) border border-line bg-surface shadow-(--shadow-card)">
      <div className="bg-linear-to-bl from-brand-800 to-brand-600 px-6 py-10 text-center text-white">
        <span className="mx-auto flex size-20 items-center justify-center rounded-3xl bg-white/15 ring-1 ring-white/25">
          <Clock className="size-10 animate-pulse" />
        </span>
        <h1 className="mt-5 text-2xl font-bold">طلبك قيد المراجعة</h1>
        <p className="mx-auto mt-2 max-w-md text-sm leading-relaxed text-brand-100">
          تراجع الإدارة بيانات شركتك ووثائقها. عند الموافقة يتحول حسابك إلى حساب شركة وتُفتح لك اللوحة.
        </p>
      </div>

      <div className="space-y-5 p-6">
        <ol className="space-y-3 text-sm">
          <TimelineItem done title="أنشأت الحساب وأكّدت البريد" />
          <TimelineItem done title="قدّمت بيانات الشركة" hint={submittedAt ? `بتاريخ ${formatDate(submittedAt)}` : undefined} />
          <TimelineItem active title="مراجعة الإدارة" hint="تستغرق عادة يوم عمل واحد" />
          <TimelineItem title="فتح لوحة الشركة" />
        </ol>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
          <p className="text-sm text-muted">سيصلك إشعار بالنتيجة. يمكنك التحقق يدوياً في أي وقت.</p>
          <Button variant="outline" loading={checking} icon={<RefreshCw className="size-4" />} onClick={onRecheck}>
            تحقق من الحالة
          </Button>
        </div>
      </div>
    </div>
  );
}

function TimelineItem({ title, hint, done = false, active = false }: { title: string; hint?: string; done?: boolean; active?: boolean }) {
  return (
    <li className="flex items-start gap-3">
      <span
        className={
          done
            ? 'mt-1 size-3 shrink-0 rounded-full bg-success-600'
            : active
              ? 'mt-1 size-3 shrink-0 animate-pulse rounded-full bg-accent-500 ring-4 ring-accent-100'
              : 'mt-1 size-3 shrink-0 rounded-full border-2 border-line-strong'
        }
      />
      <div>
        <p className={done || active ? 'font-medium text-ink' : 'text-muted'}>{title}</p>
        {hint && <p className="text-xs text-muted">{hint}</p>}
      </div>
    </li>
  );
}
