import { ShieldAlert } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';
import { isAdminSite } from '@/config/env';
import { useAuth } from '../useAuth';

/** حساب مسجَّل دخوله لكنه لا يخص هذا الموقع (حساب إدارة في موقع الشركات أو العكس). */
export function WrongSitePage() {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <AuthLayout
      title="هذا الحساب لا يخص هذا الموقع"
      description={
        isAdminSite
          ? 'موقع الإدارة مخصص لحسابات إدارة المنصة فقط.'
          : 'موقع الشركات مخصص لحسابات الشركات. حسابات الإدارة لها موقعها الخاص.'
      }
    >
      <div className="flex flex-col gap-5">
        <div className="flex items-start gap-3 rounded-2xl border border-warning-600/25 bg-warning-50 p-4 text-sm text-warning-700">
          <ShieldAlert className="mt-0.5 size-5 shrink-0" />
          <p className="leading-relaxed">سجّل الخروج ثم ادخل بالحساب المناسب لهذا الموقع.</p>
        </div>
        <Button variant="outline" onClick={handleLogout}>
          تسجيل الخروج
        </Button>
      </div>
    </AuthLayout>
  );
}
