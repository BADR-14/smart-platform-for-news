import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { BadgeCheck, Camera } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input, Select, Textarea } from '@/components/ui/FormField';
import { Avatar, DetailRow } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { ErrorState, LoadingState } from '@/components/ui/States';
import { useCategories } from '@/features/shared/categories';
import { applyServerErrors } from '@/lib/utils/form';
import { formatDate, formatNumber } from '@/lib/utils/format';
import { companyApi } from '../api';
import type { CompanyProfile } from '../api';
import { companyKeys } from '../keys';

const schema = z.object({
  name: z.string().trim().min(2, 'اسم الشركة مطلوب').max(255),
  description: z.string().trim().max(2000, 'الوصف طويل جداً'),
  website_url: z.union([z.literal(''), z.url('أدخل رابطاً صالحاً يبدأ بـ https://')]),
  category_id: z.string().min(1, 'اختر التصنيف'),
  address: z.string().trim().max(500),
  phone: z.string().trim().regex(/^\+?[0-9]{7,15}$/, 'رقم هاتف غير صالح (7 إلى 15 رقماً)'),
});

type FormValues = z.infer<typeof schema>;

export function CompanyProfilePage() {
  const query = useQuery({ queryKey: companyKeys.profile, queryFn: companyApi.profile });

  if (query.isLoading) return <LoadingState />;
  if (query.error || !query.data) return <ErrorState error={query.error} onRetry={() => query.refetch()} />;

  return <ProfileForm profile={query.data} />;
}

function ProfileForm({ profile }: { profile: CompanyProfile }) {
  const queryClient = useQueryClient();
  const categories = useCategories('company');
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [logo, setLogo] = useState<File | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const logoPreview = useMemo(() => (logo ? URL.createObjectURL(logo) : null), [logo]);
  useEffect(() => () => void (logoPreview && URL.revokeObjectURL(logoPreview)), [logoPreview]);

  const {
    register,
    handleSubmit,
    setError,
    reset,
    formState: { errors, isDirty },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: profile.name,
      description: profile.description ?? '',
      website_url: profile.website_url ?? '',
      category_id: profile.category ? String(profile.category.id) : '',
      address: profile.address ?? '',
      phone: profile.phone ?? '',
    },
  });

  const save = useMutation({
    mutationFn: (values: FormValues) => companyApi.updateProfile({ ...values, category_id: Number(values.category_id), logo }),
    onSuccess: async (response, values) => {
      toast.success(response.message ?? 'تم تحديث بيانات الشركة');
      queryClient.setQueryData(companyKeys.profile, response.data);
      // اسم وشعار الشركة يظهران في الشريط العلوي (من /me)
      await queryClient.invalidateQueries({ queryKey: ['auth', 'me'] });
      reset(values);
      setLogo(null);
    },
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      await save.mutateAsync(values);
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['name', 'description', 'website_url', 'category_id', 'address', 'phone']));
    }
  });

  return (
    <>
      <PageHeader title="ملف الشركة" description="هذه البيانات تظهر في صفحة شركتك للمستخدمين." />

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <Card>
          <CardHeader title="البيانات العامة" />
          <CardBody>
            <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
              {formError && <Alert>{formError}</Alert>}

              <div className="flex items-center gap-4">
                <Avatar name={profile.name} src={logoPreview ?? profile.logo} size="lg" />
                <div>
                  <Button variant="outline" size="sm" icon={<Camera className="size-4" />} onClick={() => fileInputRef.current?.click()}>
                    تغيير الشعار
                  </Button>
                  <p className="mt-1.5 text-xs text-muted">PNG أو JPG أو WebP، حتى 2 ميغابايت.</p>
                </div>
                <input
                  ref={fileInputRef}
                  type="file"
                  hidden
                  accept="image/png,image/jpeg,image/webp"
                  onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file && file.size > 2 * 1024 * 1024) {
                      toast.error('حجم الشعار يجب ألا يتجاوز 2 ميغابايت');
                    } else if (file) {
                      setLogo(file);
                    }

                    event.target.value = '';
                  }}
                />
              </div>

              <Input label="اسم الشركة" required error={errors.name?.message} {...register('name')} />

              <div className="grid gap-4 sm:grid-cols-2">
                <Select label="التصنيف" required error={errors.category_id?.message} {...register('category_id')}>
                  <option value="">اختر التصنيف</option>
                  {categories.data?.map((category) => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </Select>
                <Input label="رقم الهاتف" required dir="ltr" placeholder="+963912345678" error={errors.phone?.message} {...register('phone')} />
              </div>

              <Input label="الموقع الإلكتروني" dir="ltr" placeholder="https://example.com" error={errors.website_url?.message} {...register('website_url')} />
              <Input label="العنوان" error={errors.address?.message} {...register('address')} />
              <Textarea label="نبذة عن الشركة" rows={5} error={errors.description?.message} {...register('description')} />

              <div className="flex justify-end">
                <Button type="submit" loading={save.isPending} disabled={!isDirty && !logo}>
                  حفظ التغييرات
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>

        <Card className="h-fit">
          <CardHeader title="بيانات التوثيق" description="للتعديل تواصل مع الإدارة." />
          <CardBody>
            <dl className="divide-y divide-line">
              <DetailRow label="الحالة">
                {profile.is_verified ? (
                  <Badge tone="success">
                    <BadgeCheck className="size-3.5" />
                    شركة موثّقة
                  </Badge>
                ) : (
                  <Badge tone="warning">غير موثّقة</Badge>
                )}
              </DetailRow>
              <DetailRow label="البريد">
                <span className="ltr">{profile.email}</span>
              </DetailRow>
              <DetailRow label="رقم السجل التجاري">
                <span className="ltr tabular-nums">{profile.company_registration_number ?? '—'}</span>
              </DetailRow>
              <DetailRow label="الرقم الضريبي">
                <span className="ltr tabular-nums">{profile.tax_id ?? '—'}</span>
              </DetailRow>
              <DetailRow label="المتابعون">{formatNumber(profile.followers_count)}</DetailRow>
              <DetailRow label="المنشورات">{formatNumber(profile.posts_count)}</DetailRow>
              <DetailRow label="تاريخ الانضمام">{formatDate(profile.created_at)}</DetailRow>
            </dl>
          </CardBody>
        </Card>
      </div>
    </>
  );
}
