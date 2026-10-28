import { createContext } from 'react';
import type { Account } from '@/features/shared/types';
import type { LoginInput, TokenPayload } from './api';

export type AuthStatus = 'loading' | 'authenticated' | 'guest';

export interface AuthContextValue {
  status: AuthStatus;
  account: Account | null;
  /** دخول ببريد وكلمة مرور (موقع الشركات). */
  login: (input: LoginInput, remember: boolean) => Promise<Account>;
  /** يبدأ جلسة بتوكن جاهز (بعد تأكيد البريد أو بعد الرابط السحري). */
  startSession: (payload: TokenPayload, remember?: boolean) => Account;
  /** يعيد جلب بيانات الحساب (بعد تحوّله إلى حساب شركة مثلاً). */
  refreshAccount: () => Promise<Account | null>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
