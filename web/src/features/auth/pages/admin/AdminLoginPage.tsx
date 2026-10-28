import { zodResolver } from '@hookform/resolvers/zod';
import { Mail, MailCheck, Send, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { toApiError } from '@/lib/api/errors';
import { adminAuthApi } from '../../admin-api';

const schema = z.object({ email: z.email('أدخل بريداً إلكترونياً صالحاً') });

type FormValues = z.infer<typeof schema>;

/** لا يُسمح بطلب رابط جديد قبل انقضاء هذه المدة (ثوانٍ). */
const RESEND_AFTER = 60;

/**
 * دخول الإدارة بالرابط السحري: لا توجد كلمة مرور.
 * يُدخل الأدمن بريده فيصله رابط صالح لدقائق ولمرة واحدة.
 */
export function AdminLoginPage() {
  const [sentTo, setSentTo] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [secondsLeft, setSecondsLeft] = useState(0);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { email: '' } });

  useEffect(() => {
    if (secondsLeft <= 0) return;

    const timer = window.setTimeout(() => setSecondsLeft((value) => value - 1), 1000);
    return () => window.clearTimeout(timer);
  }, [secondsLeft]);

  const send = async (email: string) => {
    setFormError(null);

    try {
      await adminAuthApi.requestMagicLink(email);
      setSentTo(email);
      setSecondsLeft(RESEND_AFTER);
    } catch (error) {
      setFormError(toApiError(error).message);
    }
  };

  if (sentTo) {
    return (
      <AuthLayout title="تحقق من بريدك" description="أرسلنا رابط الدخول إذا كان البريد لحساب إدارة.">
        <div className="flex flex-col gap-5">
          <div className="flex flex-col items-center gap-3 rounded-2xl border border-success-600/20 bg-success-50 px-5 py-7 text-center">
            <span className="flex size-14 items-center justify-center rounded-2xl bg-white text-success-600 shadow-sm">
              <MailCheck className="size-7" />
            </span>
            <p className="ltr font-semibold text-ink">{sentTo}</p>
            <p className="text-sm leading-relaxed text-ink-soft">
              افتح الرسالة واضغط «دخول لوحة الإدارة». الرابط صالح لمدة 10 دقائق ولمرة واحدة فقط.
            </p>
          </div>

          {formError && <Alert>{formError}</Alert>}

          <Button variant="outline" disabled={secondsLeft > 0} onClick={() => send(sentTo)}>
            {secondsLeft > 0 ? `إعادة الإرسال بعد ${secondsLeft} ثانية` : 'إعادة إرسال الرابط'}
          </Button>
          <button type="button" className="text-sm font-medium text-accent-700 hover:underline" onClick={() => setSentTo(null)}>
            استخدام بريد آخر
          </button>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      title="دخول لوحة الإدارة"
      description="أدخل بريد حساب الإدارة وسنرسل لك رابط دخول آمن. لا حاجة لكلمة مرور."
      footer={
        <p className="flex items-start gap-2">
          <ShieldCheck className="mt-0.5 size-4 shrink-0 text-success-600" />
          هذا الموقع لإدارة المنصة فقط. كل محاولة دخول تُسجَّل.
        </p>
      }
    >
      <form onSubmit={handleSubmit(({ email }) => send(email.trim().toLowerCase()))} noValidate className="flex flex-col gap-4">
        {formError && <Alert>{formError}</Alert>}

        <Input
          label="البريد الإلكتروني"
          type="email"
          autoComplete="email"
          autoFocus
          placeholder="admin@example.com"
          dir="ltr"
          leading={<Mail className="size-4" />}
          error={errors.email?.message}
          {...register('email')}
        />

        <Button type="submit" size="lg" variant="accent" loading={isSubmitting} icon={<Send className="size-5" />}>
          أرسل رابط الدخول
        </Button>
      </form>
    </AuthLayout>
  );
}
