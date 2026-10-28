import { isAxiosError } from 'axios';

interface ApiErrorBody {
  message?: string;
  code?: string;
  errors?: Record<string, string[]>;
}

/**
 * خطأ موحّد لكل طلبات الـ API: رسالة عربية جاهزة للعرض + رمز ثابت + أخطاء الحقول.
 */
export class ApiError extends Error {
  readonly status: number;
  readonly code?: string;
  readonly fieldErrors: Record<string, string[]>;

  constructor(message: string, status: number, code?: string, fieldErrors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.fieldErrors = fieldErrors;
  }

  /** أول رسالة خطأ لحقل معيّن إن وجدت. */
  field(name: string): string | undefined {
    return this.fieldErrors[name]?.[0];
  }
}

export function toApiError(error: unknown): ApiError {
  if (error instanceof ApiError) return error;

  if (isAxiosError<ApiErrorBody>(error)) {
    if (!error.response) {
      return new ApiError('تعذّر الاتصال بالخادم. تحقق من اتصالك ثم حاول مجدداً.', 0, 'network_error');
    }

    const { status, data } = error.response;

    return new ApiError(
      data?.message ?? 'حدث خطأ غير متوقع. حاول مجدداً.',
      status,
      data?.code,
      data?.errors ?? {},
    );
  }

  return new ApiError('حدث خطأ غير متوقع. حاول مجدداً.', 0);
}

export function errorMessage(error: unknown): string {
  return toApiError(error).message;
}
