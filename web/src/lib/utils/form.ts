import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';
import { toApiError } from '@/lib/api/errors';

/**
 * يوزّع أخطاء التحقق القادمة من الـ API (422) على حقول النموذج.
 * يرجع رسالة عامة تُعرض أعلى النموذج إذا كان الخطأ لا يخص حقلاً معروفاً.
 */
export function applyServerErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
  knownFields: readonly Path<T>[],
): string | null {
  const apiError = toApiError(error);
  let matched = false;

  for (const field of knownFields) {
    const message = apiError.field(field);

    if (message) {
      setError(field, { type: 'server', message });
      matched = true;
    }
  }

  return matched ? null : apiError.message;
}
