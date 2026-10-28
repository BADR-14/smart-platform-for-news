import { zodResolver } from '@hookform/resolvers/zod';
import { Building2, FileCheck2, FileText, ImagePlus, Send, X } from 'lucide-react';
import { useEffect, useId, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input, Select, Textarea } from '@/components/ui/FormField';
import { authApi } from '@/features/auth/api';
import type { VerificationOverview } from '@/features/auth/api';
import { useCategories } from '@/features/shared/categories';
import { toApiError } from '@/lib/api/errors';
import { cn } from '@/lib/utils/cn';
import { applyServerErrors } from '@/lib/utils/form';

const schema = z.object({
  name: z.string().trim().min(2, 'اسم الشركة مطلوب').max(255),
  category_id: z.string().min(1, 'اختر تصنيف الشركة'),
  description: z.string().trim().min(10, 'اكتب وصفاً من 10 محارف على الأقل').max(2000),
  phone: z.string().trim().regex(/^\+?[0-9]{7,15}$/, 'رقم هاتف غير صالح (7 إلى 15 رقماً)'),
  website_url: z.union([z.literal(''), z.url('أدخل رابطاً صالحاً يبدأ بـ https://')]),
  address: z.string().trim().max(500),
  company_registration_number: z.string().trim().min(1, 'رقم السجل التجاري مطلوب').max(100),
  tax_id: z.string().trim().min(1, 'الرقم الضريبي مطلوب').max(100),
});

type FormValues = z.infer<typeof schema>;

type FileKey = 'logo' | 'commercial_register' | 'tax_document';

const MB = 1024 * 1024;

const fileRules: Record<FileKey, { label: string; hint: string; accept: string; maxBytes: number; required: string }> = {
  logo: {
    label: 'شعار الشركة',
    hint: 'PNG أو JPG أو WEBP — حتى 2 ميغابايت',
    accept: 'image/png,image/jpeg,image/webp',
    maxBytes: 2 * MB,
    required: 'شعار الشركة مطلوب',
  },
  commercial_register: {
    label: 'السجل التجاري',
    hint: 'صورة أو PDF — حتى 10 ميغابايت',
    accept: 'image/png,image/jpeg,application/pdf',
    maxBytes: 10 * MB,
    required: 'صورة السجل التجاري مطلوبة',
  },
  tax_document: {
    label: 'الوثيقة الضريبية',
    hint: 'صورة أو PDF — حتى 10 ميغابايت',
    accept: 'image/png,image/jpeg,application/pdf',
    maxBytes: 10 * MB,
    required: 'الوثيقة الضريبية مطلوبة',
  },
};

interface CompanyRequestFormProps {
  defaultName: string;
  onSubmitted: (overview: VerificationOverview) => void;
}

/** نموذج طلب إنشاء شركة: بيانات الشركة + الشعار + الوثائق الرسمية. */
export function CompanyRequestForm({ defaultName, onSubmitted }: CompanyRequestFormProps) {
  const categories = useCategories('company');
  const [files, setFiles] = useState<Partial<Record<FileKey, File>>>({});
  const [fileErrors, setFileErrors] = useState<Partial<Record<FileKey, string>>>({});
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: defaultName,
      category_id: '',
      description: '',
      phone: '',
      website_url: '',
      address: '',
      company_registration_number: '',
      tax_id: '',
    },
  });

  const setFile = (key: FileKey, file: File | undefined) => {
    if (file && file.size > fileRules[key].maxBytes) {
      setFileErrors((current) => ({ ...current, [key]: `حجم الملف يتجاوز ${fileRules[key].maxBytes / MB} ميغابايت` }));
      return;
    }

    setFiles((current) => ({ ...current, [key]: file }));
    setFileErrors((current) => ({ ...current, [key]: undefined }));
  };

  const validateFiles = (): boolean => {
    const missing = (Object.keys(fileRules) as FileKey[]).filter((key) => !files[key]);

    setFileErrors((current) => ({ ...current, ...Object.fromEntries(missing.map((key) => [key, fileRules[key].required])) }));

    return missing.length === 0;
  };

  const onSubmit = handleSubmit(
    async (values) => {
      if (!validateFiles()) return;

      setFormError(null);

      const form = new FormData();
      form.append('name', values.name);
      form.append('category_id', values.category_id);
      form.append('description', values.description);
      form.append('phone', values.phone);
      form.append('company_registration_number', values.company_registration_number);
      form.append('tax_id', values.tax_id);
      if (values.website_url) form.append('website_url', values.website_url);
      if (values.address) form.append('address', values.address);
      form.append('logo', files.logo!);
      form.append('documents[commercial_register]', files.commercial_register!);
      form.append('documents[tax_document]', files.tax_document!);

      try {
        onSubmitted(await authApi.submitCompanyRequest(form));
      } catch (error) {
        const apiError = toApiError(error);

        // أخطاء الملفات تأتي بمفاتيح مثل documents.tax_document
        setFileErrors({
          logo: apiError.field('logo'),
          commercial_register: apiError.field('documents.commercial_register'),
          tax_document: apiError.field('documents.tax_document'),
        });

        setFormError(
          applyServerErrors(error, setError, [
            'name',
            'category_id',
            'description',
            'phone',
            'website_url',
            'address',
            'company_registration_number',
            'tax_id',
          ]),
        );
      }
    },
    // حتى مع أخطاء حقول النص نُظهر أخطاء الملفات الناقصة في نفس المحاولة
    () => {
      validateFiles();
    },
  );

  return (
    <form onSubmit={onSubmit} noValidate className="space-y-6">
      {formError && <Alert>{formError}</Alert>}

      <Card>
        <CardHeader title="بيانات الشركة" description="تظهر هذه البيانات في صفحة شركتك أمام مستخدمي المنصة." />
        <CardBody className="grid gap-5 sm:grid-cols-2">
          <Input
            label="اسم الشركة"
            required
            leading={<Building2 className="size-4" />}
            error={errors.name?.message}
            {...register('name')}
          />
          <Select label="التصنيف" required error={errors.category_id?.message} disabled={categories.isLoading} {...register('category_id')}>
            <option value="">{categories.isLoading ? 'جارِ التحميل…' : 'اختر التصنيف'}</option>
            {categories.data?.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </Select>
          <Textarea
            label="وصف الشركة"
            required
            rows={4}
            wrapperClassName="sm:col-span-2"
            placeholder="ماذا تقدّم الشركة؟ وما نوع الفعاليات التي تنظّمها؟"
            error={errors.description?.message}
            {...register('description')}
          />
          <Input label="هاتف الشركة" required dir="ltr" placeholder="+963112345678" error={errors.phone?.message} {...register('phone')} />
          <Input
            label="الموقع الإلكتروني"
            dir="ltr"
            placeholder="https://example.com"
            hint="اختياري"
            error={errors.website_url?.message}
            {...register('website_url')}
          />
          <Input
            label="العنوان"
            wrapperClassName="sm:col-span-2"
            placeholder="المدينة، الشارع، البناء"
            hint="اختياري"
            error={errors.address?.message}
            {...register('address')}
          />
          <FileField fieldKey="logo" file={files.logo} error={fileErrors.logo} onChange={(file) => setFile('logo', file)} className="sm:col-span-2" />
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="الوثائق الرسمية" description="تُستخدم للتحقق فقط ولا تظهر لأي مستخدم." />
        <CardBody className="grid gap-5 sm:grid-cols-2">
          <Input
            label="رقم السجل التجاري"
            required
            dir="ltr"
            error={errors.company_registration_number?.message}
            {...register('company_registration_number')}
          />
          <Input label="الرقم الضريبي" required dir="ltr" error={errors.tax_id?.message} {...register('tax_id')} />
          <FileField
            fieldKey="commercial_register"
            file={files.commercial_register}
            error={fileErrors.commercial_register}
            onChange={(file) => setFile('commercial_register', file)}
          />
          <FileField
            fieldKey="tax_document"
            file={files.tax_document}
            error={fileErrors.tax_document}
            onChange={(file) => setFile('tax_document', file)}
          />
        </CardBody>
      </Card>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-muted">بعد الإرسال تراجع الإدارة طلبك، وعند الموافقة يتحول حسابك إلى حساب شركة.</p>
        <Button type="submit" size="lg" variant="accent" loading={isSubmitting} icon={<Send className="size-5" />}>
          إرسال الطلب
        </Button>
      </div>
    </form>
  );
}

interface FileFieldProps {
  fieldKey: FileKey;
  file: File | undefined;
  error: string | undefined;
  onChange: (file: File | undefined) => void;
  className?: string;
}

/** حقل رفع ملف واحد مع معاينة للصور. */
function FileField({ fieldKey, file, error, onChange, className }: FileFieldProps) {
  const id = useId();
  const rule = fileRules[fieldKey];
  // رابط المعاينة يُنشأ للصور فقط ويُحرَّر عند تغيّر الملف
  const preview = useMemo(() => (file?.type.startsWith('image/') ? URL.createObjectURL(file) : null), [file]);

  useEffect(() => {
    return () => {
      if (preview) URL.revokeObjectURL(preview);
    };
  }, [preview]);

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <span className="text-sm font-medium text-ink-soft">
        {rule.label}
        <span className="ms-1 text-danger-600">*</span>
      </span>

      {file ? (
        <div className={cn('flex items-center gap-3 rounded-xl border bg-success-50/60 p-3', error ? 'border-danger-600' : 'border-success-600/30')}>
          {preview ? (
            <img src={preview} alt="" className="size-14 shrink-0 rounded-lg border border-line object-cover" />
          ) : (
            <span className="flex size-14 shrink-0 items-center justify-center rounded-lg bg-white text-success-600">
              <FileCheck2 className="size-6" />
            </span>
          )}
          <div className="min-w-0 flex-1">
            <p className="ltr truncate text-end text-sm font-medium text-ink">{file.name}</p>
            <p className="text-xs text-muted">{(file.size / MB).toFixed(2)} ميغابايت</p>
          </div>
          <button
            type="button"
            aria-label={`إزالة ${rule.label}`}
            className="rounded-lg p-1.5 text-muted transition-colors hover:bg-white hover:text-danger-600"
            onClick={() => onChange(undefined)}
          >
            <X className="size-4" />
          </button>
        </div>
      ) : (
        <label
          htmlFor={id}
          className={cn(
            'flex cursor-pointer items-center gap-3 rounded-xl border border-dashed p-4 transition-colors hover:border-accent-400 hover:bg-accent-50/50',
            error ? 'border-danger-600 bg-danger-50/40' : 'border-line-strong bg-canvas/60',
          )}
        >
          <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm">
            {fieldKey === 'logo' ? <ImagePlus className="size-5" /> : <FileText className="size-5" />}
          </span>
          <span className="min-w-0">
            <span className="block text-sm font-medium text-ink">اضغط لاختيار ملف</span>
            <span className="block text-xs text-muted">{rule.hint}</span>
          </span>
        </label>
      )}

      <input
        id={id}
        type="file"
        accept={rule.accept}
        className="sr-only"
        onChange={(event) => {
          onChange(event.target.files?.[0]);
          // يسمح باختيار نفس الملف مرة أخرى بعد إزالته
          event.target.value = '';
        }}
      />

      {error && (
        <p role="alert" className="text-xs text-danger-600">
          {error}
        </p>
      )}
    </div>
  );
}
