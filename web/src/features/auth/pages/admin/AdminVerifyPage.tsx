import { useMutation } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { errorMessage } from '@/lib/api/errors';
import { adminAuthApi } from '../../admin-api';
import { useAuth } from '../../useAuth';

/**
 * الصفحة التي يفتحها الرابط السحري من البريد: تستهلك الرابط وتبدأ الجلسة.
 */
export function AdminVerifyPage() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const { startSession } = useAuth();
  const started = useRef(false);

  const email = params.get('email') ?? '';
  const token = params.get('token') ?? '';

  const verify = useMutation({
    mutationFn: () => adminAuthApi.verifyMagicLink(email, token),
    onSuccess: (payload) => {
      // جلسة الإدارة لا تُحفظ بعد إغلاق المتصفح
      startSession(payload, false);
      navigate('/admin', { replace: true });
    },
  });

  // الرابط يُستهلك مرة واحدة: لا نرسله مرتين (StrictMode يشغّل التأثير مرتين في التطوير)
  useEffect(() => {
    if (started.current || !email || !token) return;

    started.current = true;
    verify.mutate();
  }, [email, token, verify]);

  if (!email || !token) {
    return (
      <AuthLayout title="رابط غير مكتمل" description="افتح الرابط كما وصلك في البريد، أو اطلب رابطاً جديداً.">
        <Link to="/login">
          <Button className="w-full">طلب رابط جديد</Button>
        </Link>
      </AuthLayout>
    );
  }

  if (verify.isError) {
    return (
      <AuthLayout title="تعذّر تسجيل الدخول">
        <div className="flex flex-col gap-4">
          <Alert>{errorMessage(verify.error)}</Alert>
          <Link to="/login">
            <Button className="w-full">طلب رابط جديد</Button>
          </Link>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout title="جارٍ تسجيل الدخول…" description="نتحقق من رابط الدخول.">
      <div className="flex justify-center py-8 text-accent-500">
        <Loader2 className="size-9 animate-spin" aria-label="جارِ التحقق" />
      </div>
    </AuthLayout>
  );
}
