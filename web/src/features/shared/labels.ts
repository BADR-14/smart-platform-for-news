import type { BadgeTone } from '@/components/ui/Badge';
import type { PostStatus, PostType, VerificationStatus } from './types';

/** النصوص العربية والألوان لكل حالة/نوع — مكان واحد حتى تبقى الواجهة متسقة. */

export const postStatusMeta: Record<PostStatus, { label: string; tone: BadgeTone }> = {
  published: { label: 'منشور', tone: 'success' },
  draft: { label: 'مسودة', tone: 'neutral' },
  under_review: { label: 'قيد المراجعة', tone: 'warning' },
  rejected: { label: 'مرفوض', tone: 'danger' },
  archived: { label: 'مؤرشف', tone: 'info' },
};

export const postTypeLabels: Record<PostType, string> = {
  lecture: 'محاضرة',
  exhibition: 'معرض',
  event: 'فعالية',
  course: 'دورة',
};

export const verificationStatusMeta: Record<VerificationStatus, { label: string; tone: BadgeTone }> = {
  approved: { label: 'موثّق', tone: 'success' },
  pending: { label: 'قيد المراجعة', tone: 'warning' },
  rejected: { label: 'مرفوض', tone: 'danger' },
  not_submitted: { label: 'غير موثّق', tone: 'neutral' },
};

/** أسباب الرفض الآلية القادمة من قارئ الوثائق. */
export const rejectionReasonLabels: Record<string, string> = {
  name_mismatch: 'الاسم لا يطابق الوثيقة',
  id_mismatch: 'الرقم لا يطابق الوثيقة',
  invalid_date: 'لم يُعثر على تاريخ صالح',
  low_image_quality: 'الصورة غير واضحة',
};

/** طريقة دفع المنشور المميز. */
export const paymentMethodLabels = { wallet: 'من المحفظة', plan: 'من الباقة' } as const;

export function rejectionReasonLabel(reason: string): string {
  return rejectionReasonLabels[reason] ?? reason;
}
