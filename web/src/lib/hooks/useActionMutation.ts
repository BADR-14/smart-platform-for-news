import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { QueryKey } from '@tanstack/react-query';
import { toast } from 'sonner';
import { errorMessage } from '@/lib/api/errors';

interface Options<TVariables> {
  mutationFn: (variables: TVariables) => Promise<string | void>;
  /** مفاتيح الاستعلامات التي يجب إعادة جلبها بعد نجاح الإجراء. */
  invalidate: QueryKey[];
  onSuccess?: () => void;
  successMessage?: string;
  /** النماذج تعرض الخطأ داخلها (بجانب الحقل)، فتعطّل رسالة الخطأ المنبثقة. */
  errorToast?: boolean;
}

/**
 * نمط موحّد لإجراءات الجداول (حظر، حذف، تفعيل ...):
 * ينفّذ الطلب، يعرض رسالة النجاح القادمة من الـ API أو رسالة الخطأ، ويحدّث البيانات المتأثرة.
 */
export function useActionMutation<TVariables = void>({
  mutationFn,
  invalidate,
  onSuccess,
  successMessage,
  errorToast = true,
}: Options<TVariables>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn,
    onSuccess: async (message) => {
      toast.success(successMessage ?? message ?? 'تم تنفيذ الإجراء');
      await Promise.all(invalidate.map((queryKey) => queryClient.invalidateQueries({ queryKey })));
      onSuccess?.();
    },
    onError: (error) => {
      if (errorToast) toast.error(errorMessage(error));
    },
  });
}
