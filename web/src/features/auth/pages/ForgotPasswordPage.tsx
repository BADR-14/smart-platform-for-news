import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Mail } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { z } from 'zod';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { errorMessage } from '@/lib/api/errors';
import { authApi } from '../api';

const schema = z.object({ email: z.email('أدخل بريداً إلكترونياً صالحاً') });

type FormValues = z.infer<typeof schema>;

export function ForgotPasswordPage() {
  const [sentMessage, setSentMessage] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { email: '' } });

  const onSubmit = handleSubmit(async ({ email }) => {
    setFormError(null);

    try {
      setSentMessage(await authApi.sendResetLink(email));
    } catch (error) {
      setFormError(errorMessage(error));
    }
  });

  return (
    <AuthLayout
      title="استعادة كلمة المرور"
      description="أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور."
      footer={
        <Link to="/login" className="inline-flex items-center gap-1.5 font-medium text-accent-700 hover:underline">
          <ArrowRight className="size-4" />
          العودة لتسجيل الدخول
        </Link>
      }
    >
      {sentMessage ? (
        <Alert tone="success" title="تحقق من بريدك">
          {sentMessage}
        </Alert>
      ) : (
        <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
          {formError && <Alert>{formError}</Alert>}

          <Input
            label="البريد الإلكتروني"
            type="email"
            autoComplete="email"
            dir="ltr"
            leading={<Mail className="size-4" />}
            error={errors.email?.message}
            {...register('email')}
          />

          <Button type="submit" size="lg" loading={isSubmitting}>
            إرسال رابط إعادة التعيين
          </Button>
        </form>
      )}
    </AuthLayout>
  );
}
