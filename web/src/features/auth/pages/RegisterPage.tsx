import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, KeyRound, Lock, Mail, User } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from 'sonner';
import { z } from 'zod';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { Stepper } from '@/components/ui/Stepper';
import { errorMessage } from '@/lib/api/errors';
import { applyServerErrors } from '@/lib/utils/form';
import { authApi } from '../api';
import { REGISTRATION_STEPS } from '../registration-steps';
import { passwordSchema } from '../schemas';
import { useAuth } from '../useAuth';

const accountSchema = z
  .object({
    name: z.string().trim().min(2, 'اكتب اسمك (حرفان على الأقل)').max(255),
    email: z.email('أدخل بريداً إلكترونياً صالحاً'),
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'أعد كتابة كلمة المرور'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ['password_confirmation'],
    message: 'كلمتا المرور غير متطابقتين',
  });

type AccountValues = z.infer<typeof accountSchema>;

const RESEND_AFTER = 60;

/**
 * تسجيل شركة جديدة مباشرة من موقع الشركات:
 * 1) إنشاء الحساب  2) تأكيد البريد برمز  ثم ينتقل لصفحة بيانات الشركة.
 */
export function RegisterPage() {
  const [params] = useSearchParams();
  // قادم من صفحة الدخول بحساب لم يؤكَّد بريده: نبدأ من خطوة الرمز
  const pendingEmail = params.get('email');

  const [email, setEmail] = useState<string | null>(pendingEmail);

  return email ? (
    <OtpStep email={email} resendOnMount={pendingEmail !== null} onBack={() => setEmail(null)} />
  ) : (
    <AccountStep onCreated={setEmail} />
  );
}

function AccountStep({ onCreated }: { onCreated: (email: string) => void }) {
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<AccountValues>({
    resolver: zodResolver(accountSchema),
    defaultValues: { name: '', email: '', password: '', password_confirmation: '' },
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);
    const email = values.email.trim().toLowerCase();

    try {
      await authApi.register({ ...values, email });
      onCreated(email);
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['name', 'email', 'password']));
    }
  });

  return (
    <AuthLayout
      title="سجّل شركتك"
      description="أنشئ حساب المسؤول عن الشركة أولاً، ثم أضف بيانات الشركة ووثائقها."
      footer={
        <p>
          لديك حساب؟{' '}
          <Link to="/login" className="font-semibold text-accent-700 hover:underline">
            سجّل الدخول
          </Link>
        </p>
      }
    >
      <Stepper steps={REGISTRATION_STEPS} current={0} className="mb-7" />

      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
        {formError && <Alert>{formError}</Alert>}

        <Input
          label="اسم المسؤول"
          autoComplete="name"
          placeholder="الاسم الكامل"
          leading={<User className="size-4" />}
          error={errors.name?.message}
          {...register('name')}
        />
        <Input
          label="البريد الإلكتروني للعمل"
          type="email"
          autoComplete="email"
          placeholder="name@company.com"
          dir="ltr"
          leading={<Mail className="size-4" />}
          error={errors.email?.message}
          {...register('email')}
        />
        <Input
          label="كلمة المرور"
          type="password"
          autoComplete="new-password"
          dir="ltr"
          hint="8 محارف على الأقل مع حرف ورقم"
          leading={<Lock className="size-4" />}
          error={errors.password?.message}
          {...register('password')}
        />
        <Input
          label="تأكيد كلمة المرور"
          type="password"
          autoComplete="new-password"
          dir="ltr"
          leading={<Lock className="size-4" />}
          error={errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />

        <Button type="submit" size="lg" variant="accent" loading={isSubmitting} icon={<ArrowLeft className="size-5" />}>
          متابعة
        </Button>
      </form>
    </AuthLayout>
  );
}

interface OtpStepProps {
  email: string;
  resendOnMount: boolean;
  onBack: () => void;
}

function OtpStep({ email, resendOnMount, onBack }: OtpStepProps) {
  const { startSession } = useAuth();
  const navigate = useNavigate();
  const [code, setCode] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [secondsLeft, setSecondsLeft] = useState(RESEND_AFTER);

  useEffect(() => {
    if (secondsLeft <= 0) return;

    const timer = window.setTimeout(() => setSecondsLeft((value) => value - 1), 1000);
    return () => window.clearTimeout(timer);
  }, [secondsLeft]);

  // حساب قديم غير مؤكد: نرسل له رمزاً جديداً تلقائياً
  useEffect(() => {
    if (resendOnMount) authApi.resendOtp(email).catch(() => undefined);
  }, [resendOnMount, email]);

  const submit = async (value: string) => {
    if (value.length !== 6 || submitting) return;

    setSubmitting(true);
    setFormError(null);

    try {
      startSession(await authApi.verifyOtp(email, value));
      navigate('/onboarding', { replace: true });
    } catch (error) {
      setFormError(errorMessage(error));
      setSubmitting(false);
    }
  };

  const resend = async () => {
    try {
      toast.success(await authApi.resendOtp(email));
      setSecondsLeft(RESEND_AFTER);
    } catch (error) {
      toast.error(errorMessage(error));
    }
  };

  return (
    <AuthLayout title="تأكيد البريد الإلكتروني" description="أدخل الرمز المكوّن من 6 أرقام الذي أرسلناه إلى بريدك.">
      <Stepper steps={REGISTRATION_STEPS} current={1} className="mb-7" />

      <div className="flex flex-col gap-4">
        <p className="ltr rounded-xl bg-brand-50 px-4 py-2.5 text-center text-sm font-semibold text-brand-800">{email}</p>

        {formError && <Alert>{formError}</Alert>}

        <Input
          label="رمز التحقق"
          inputMode="numeric"
          autoComplete="one-time-code"
          autoFocus
          maxLength={6}
          dir="ltr"
          placeholder="••••••"
          className="text-center text-xl font-bold tracking-[0.6em]"
          leading={<KeyRound className="size-4" />}
          value={code}
          onChange={(event) => {
            const value = event.target.value.replace(/\D/g, '').slice(0, 6);
            setCode(value);
            if (value.length === 6) void submit(value);
          }}
        />

        <Button size="lg" variant="accent" loading={submitting} disabled={code.length !== 6} onClick={() => submit(code)}>
          تأكيد ومتابعة
        </Button>

        <div className="flex items-center justify-between text-sm">
          <button type="button" className="font-medium text-muted hover:text-ink" onClick={onBack}>
            تغيير البريد
          </button>
          {secondsLeft > 0 ? (
            <span className="text-muted">إعادة الإرسال بعد {secondsLeft} ثانية</span>
          ) : (
            <button type="button" className="font-medium text-accent-700 hover:underline" onClick={resend}>
              إعادة إرسال الرمز
            </button>
          )}
        </div>
      </div>
    </AuthLayout>
  );
}
