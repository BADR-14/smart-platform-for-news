import { api } from '@/lib/api/client';
import type { ApiResponse, MessageResponse } from '@/lib/api/types';
import type { Account, VerificationStatus } from '@/features/shared/types';

export interface TokenPayload {
  access_token: string;
  token_type: 'Bearer';
  account: Account;
}

export interface LoginInput {
  email: string;
  password: string;
}

export interface RegisterInput {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface ResetPasswordInput {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface ChangePasswordInput {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}

/** ملخص آخر طلب توثيق من نوع معيّن (مستخدم أو شركة). */
export interface VerificationSummary {
  status: VerificationStatus;
  rejection_reasons: string[];
  attempts_left: number;
  can_submit: boolean;
  submitted_at: string | null;
}

export interface VerificationOverview {
  status: VerificationStatus;
  is_verified: boolean;
  account_type: Account['type'];
  user: VerificationSummary;
  company: VerificationSummary;
}

export const authApi = {
  // ---------- موقع الشركات: بريد وكلمة مرور ----------

  async login(input: LoginInput): Promise<TokenPayload> {
    const { data } = await api.post<ApiResponse<TokenPayload>>('/login', { ...input, device_name: 'company-dashboard' });
    return data.data;
  },

  /** ينشئ الحساب ويرسل رمز التحقق إلى البريد. لا يسجّل الدخول. */
  async register(input: RegisterInput): Promise<string> {
    const { data } = await api.post<MessageResponse>('/register', input);
    return data.message;
  },

  async verifyOtp(email: string, otpCode: string): Promise<TokenPayload> {
    const { data } = await api.post<ApiResponse<TokenPayload>>('/verify-otp', {
      email,
      otp_code: otpCode,
      device_name: 'company-dashboard',
    });
    return data.data;
  },

  async resendOtp(email: string): Promise<string> {
    const { data } = await api.post<MessageResponse>('/resend-otp', { email });
    return data.message;
  },

  // ---------- مشترك ----------

  async me(): Promise<Account> {
    const { data } = await api.get<ApiResponse<Account>>('/me');
    return data.data;
  },

  async logout(): Promise<void> {
    await api.post('/logout');
  },

  async sendResetLink(email: string): Promise<string> {
    const { data } = await api.post<MessageResponse>('/password/email', { email });
    return data.message;
  },

  async resetPassword(input: ResetPasswordInput): Promise<string> {
    const { data } = await api.post<MessageResponse>('/password/reset', input);
    return data.message;
  },

  async changePassword(input: ChangePasswordInput): Promise<string> {
    const { data } = await api.post<MessageResponse>('/settings/password', input);
    return data.message;
  },

  // ---------- طلب إنشاء شركة ----------

  async verificationStatus(): Promise<VerificationOverview> {
    const { data } = await api.get<ApiResponse<VerificationOverview>>('/profile/verification-status');
    return data.data;
  },

  /** طلب إنشاء شركة وتوثيقها (multipart: بيانات + شعار + وثيقتان). */
  async submitCompanyRequest(form: FormData): Promise<VerificationOverview> {
    const { data } = await api.post<ApiResponse<{ verification: VerificationOverview }>>('/companies', form);
    return data.data.verification;
  },
};
