import { CheckCircle2, ExternalLink, FileText, Plus, X, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';
import { DetailRow } from '@/components/ui/Misc';
import { Modal } from '@/components/ui/Modal';
import { rejectionReasonLabel } from '@/features/shared/labels';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { formatDateTime } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { VerificationDocument, VerificationRequest } from '../api';
import { adminKeys } from '../keys';
import { ScoreBadge } from './ScoreBadge';

const documentLabels: Record<VerificationDocument['document_type'], string> = {
  identity_front: 'الهوية — الوجه الأمامي',
  identity_back: 'الهوية — الوجه الخلفي',
  commercial_register: 'السجل التجاري',
  tax_document: 'الوثيقة الضريبية',
};

const commonReasons = ['الصورة غير واضحة', 'البيانات لا تطابق الوثيقة', 'الوثيقة منتهية الصلاحية', 'وثيقة غير مقبولة'];

interface Props {
  request: VerificationRequest;
  onClose: () => void;
}

export function VerificationReviewDialog({ request, onClose }: Props) {
  const [rejecting, setRejecting] = useState(false);
  const [reasons, setReasons] = useState<string[]>([]);
  const [customReason, setCustomReason] = useState('');

  const isPending = request.status === 'pending';
  const isCompany = request.type === 'company';

  const review = useActionMutation({
    mutationFn: (decision: 'approved' | 'rejected') =>
      adminApi.reviewVerification(request.id, {
        decision,
        rejection_reasons: decision === 'rejected' ? reasons : undefined,
      }),
    invalidate: [adminKeys.verification, adminKeys.overview, adminKeys.users, adminKeys.companies],
    onSuccess: onClose,
  });

  const toggleReason = (reason: string) =>
    setReasons((current) => (current.includes(reason) ? current.filter((item) => item !== reason) : [...current, reason]));

  const addCustomReason = () => {
    const value = customReason.trim();

    if (value && !reasons.includes(value)) setReasons((current) => [...current, value]);
    setCustomReason('');
  };

  const footer = !isPending ? (
    <Button variant="outline" onClick={onClose}>
      إغلاق
    </Button>
  ) : rejecting ? (
    <>
      <Button variant="ghost" onClick={() => setRejecting(false)} disabled={review.isPending}>
        رجوع
      </Button>
      <Button
        variant="danger"
        icon={<XCircle className="size-4" />}
        disabled={reasons.length === 0}
        loading={review.isPending}
        onClick={() => review.mutate('rejected')}
      >
        تأكيد الرفض
      </Button>
    </>
  ) : (
    <>
      <Button variant="danger-outline" icon={<XCircle className="size-4" />} onClick={() => setRejecting(true)} disabled={review.isPending}>
        رفض
      </Button>
      <Button icon={<CheckCircle2 className="size-4" />} loading={review.isPending} onClick={() => review.mutate('approved')}>
        {isCompany ? 'الموافقة وتحويل الحساب إلى شركة' : 'الموافقة وتوثيق الحساب'}
      </Button>
    </>
  );

  return (
    <Modal
      open
      onClose={onClose}
      size="xl"
      title={isCompany ? 'طلب إنشاء شركة' : 'طلب توثيق مستخدم'}
      description={`قُدّم في ${formatDateTime(request.submitted_at)}`}
      footer={footer}
    >
      <div className="grid gap-6 lg:grid-cols-2">
        {/* البيانات المدخلة */}
        <div className="space-y-4">
          <section>
            <h3 className="mb-1 text-sm font-semibold text-ink">صاحب الطلب</h3>
            <dl className="divide-y divide-line rounded-xl bg-canvas px-4">
              <DetailRow label="الاسم">{request.account?.name}</DetailRow>
              <DetailRow label="البريد">
                <span className="ltr">{request.account?.email}</span>
              </DetailRow>
              {!isCompany && (
                <DetailRow label="الرقم الوطني">
                  <span className="ltr tabular-nums">{request.account?.national_id ?? '—'}</span>
                </DetailRow>
              )}
              {request.account?.linkedin_url && (
                <DetailRow label="LinkedIn">
                  <a
                    href={request.account.linkedin_url}
                    target="_blank"
                    rel="noreferrer noopener"
                    className="inline-flex items-center gap-1 text-accent-700 hover:underline"
                  >
                    فتح <ExternalLink className="size-3.5" />
                  </a>
                </DetailRow>
              )}
            </dl>
          </section>

          {request.company && (
            <section>
              <h3 className="mb-1 text-sm font-semibold text-ink">بيانات الشركة</h3>
              <dl className="divide-y divide-line rounded-xl bg-canvas px-4">
                <DetailRow label="اسم الشركة">{request.company.name}</DetailRow>
                <DetailRow label="التصنيف">{request.company.category ?? '—'}</DetailRow>
                <DetailRow label="رقم السجل التجاري">
                  <span className="ltr tabular-nums">{request.company.company_registration_number ?? '—'}</span>
                </DetailRow>
                <DetailRow label="الرقم الضريبي">
                  <span className="ltr tabular-nums">{request.company.tax_id ?? '—'}</span>
                </DetailRow>
                <DetailRow label="الهاتف">
                  <span className="ltr">{request.company.phone ?? '—'}</span>
                </DetailRow>
                <DetailRow label="الموقع الإلكتروني">
                  {request.company.website_url ? (
                    <a href={request.company.website_url} target="_blank" rel="noreferrer noopener" className="ltr text-accent-700 hover:underline">
                      {request.company.website_url}
                    </a>
                  ) : (
                    '—'
                  )}
                </DetailRow>
                <DetailRow label="العنوان">{request.company.address ?? '—'}</DetailRow>
              </dl>
              {request.company.description && (
                <p className="mt-3 rounded-xl bg-canvas p-4 text-sm leading-relaxed text-ink-soft">{request.company.description}</p>
              )}
            </section>
          )}

          <section>
            <h3 className="mb-1 text-sm font-semibold text-ink">القراءة الآلية</h3>
            <dl className="divide-y divide-line rounded-xl bg-canvas px-4">
              <DetailRow label="النتيجة">
                <ScoreBadge score={request.confidence_score} />
              </DetailRow>
              {request.rejection_reasons.length > 0 && (
                <DetailRow label={request.status === 'rejected' ? 'أسباب الرفض' : 'ملاحظات القراءة'}>
                  <span className="flex flex-wrap justify-end gap-1.5">
                    {request.rejection_reasons.map((reason) => (
                      <Badge key={reason} tone="warning">
                        {rejectionReasonLabel(reason)}
                      </Badge>
                    ))}
                  </span>
                </DetailRow>
              )}
              {request.reviewed_by?.name && <DetailRow label="راجعه">{request.reviewed_by.name}</DetailRow>}
              {request.reviewed_at && <DetailRow label="تاريخ القرار">{formatDateTime(request.reviewed_at)}</DetailRow>}
            </dl>
          </section>
        </div>

        {/* الوثائق */}
        <section>
          <h3 className="mb-2 text-sm font-semibold text-ink">الوثائق المرفوعة</h3>
          {request.documents.length === 0 && (
            <p className="rounded-xl border border-dashed border-line-strong p-6 text-center text-sm text-muted">لا توجد وثائق مرفقة بهذا الطلب.</p>
          )}
          <div className="space-y-4">
            {request.documents.map((document) => (
              <figure key={document.id} className="overflow-hidden rounded-xl border border-line">
                <figcaption className="flex items-center justify-between gap-2 border-b border-line bg-canvas px-3 py-2 text-sm font-medium text-ink-soft">
                  {documentLabels[document.document_type]}
                  <a
                    href={document.file_url}
                    target="_blank"
                    rel="noreferrer noopener"
                    className="inline-flex items-center gap-1 text-xs text-accent-700 hover:underline"
                  >
                    فتح بالحجم الكامل <ExternalLink className="size-3.5" />
                  </a>
                </figcaption>
                {document.is_image ? (
                  <img src={document.file_url} alt={documentLabels[document.document_type]} className="max-h-72 w-full bg-brand-50 object-contain" />
                ) : (
                  <div className="flex items-center gap-2 p-4 text-sm text-muted">
                    <FileText className="size-5" />
                    ملف PDF — افتحه بالحجم الكامل للمعاينة.
                  </div>
                )}
                {document.extracted_text && (
                  <details className="border-t border-line px-3 py-2 text-xs text-muted">
                    <summary className="cursor-pointer select-none font-medium">النص المقروء آلياً</summary>
                    <p className="ltr mt-2 max-h-32 overflow-y-auto whitespace-pre-wrap text-start leading-relaxed">{document.extracted_text}</p>
                  </details>
                )}
              </figure>
            ))}
          </div>
        </section>
      </div>

      {/* أسباب الرفض */}
      {isPending && rejecting && (
        <div className="mt-6 rounded-xl border border-danger-600/25 bg-danger-50/60 p-4">
          <p className="mb-3 text-sm font-semibold text-danger-700">أسباب الرفض (تظهر لصاحب الطلب)</p>

          <div className="flex flex-wrap gap-2">
            {commonReasons.map((reason) => {
              const active = reasons.includes(reason);

              return (
                <button
                  key={reason}
                  type="button"
                  onClick={() => toggleReason(reason)}
                  aria-pressed={active}
                  className={
                    active
                      ? 'rounded-full bg-danger-600 px-3 py-1 text-xs font-medium text-white'
                      : 'rounded-full border border-line-strong bg-surface px-3 py-1 text-xs font-medium text-ink-soft hover:border-danger-600'
                  }
                >
                  {reason}
                </button>
              );
            })}
          </div>

          <div className="mt-3 flex gap-2">
            <Input
              wrapperClassName="flex-1"
              placeholder="سبب آخر…"
              maxLength={255}
              value={customReason}
              onChange={(event) => setCustomReason(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === 'Enter') {
                  event.preventDefault();
                  addCustomReason();
                }
              }}
            />
            <Button variant="outline" icon={<Plus className="size-4" />} onClick={addCustomReason} disabled={!customReason.trim()}>
              إضافة
            </Button>
          </div>

          {reasons.some((reason) => !commonReasons.includes(reason)) && (
            <ul className="mt-3 flex flex-wrap gap-2">
              {reasons
                .filter((reason) => !commonReasons.includes(reason))
                .map((reason) => (
                  <li key={reason} className="inline-flex items-center gap-1 rounded-full bg-danger-600 py-1 pe-1.5 ps-3 text-xs font-medium text-white">
                    {reason}
                    <button type="button" onClick={() => toggleReason(reason)} aria-label={`إزالة ${reason}`}>
                      <X className="size-3.5" />
                    </button>
                  </li>
                ))}
            </ul>
          )}
        </div>
      )}

      {isPending && !rejecting && isCompany && (
        <Alert tone="info" className="mt-6">
          عند الموافقة يتحول حساب صاحب الطلب إلى حساب شركة باسم «{request.company?.name}» ويستطيع الدخول إلى لوحة الشركة.
        </Alert>
      )}
    </Modal>
  );
}
