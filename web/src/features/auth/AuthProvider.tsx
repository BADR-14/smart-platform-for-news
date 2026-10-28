import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { setUnauthorizedHandler } from '@/lib/api/client';
import { tokenStorage } from '@/lib/api/token-storage';
import type { Account } from '@/features/shared/types';
import { authApi } from './api';
import type { LoginInput, TokenPayload } from './api';
import { AuthContext } from './auth-context';
import type { AuthContextValue } from './auth-context';

const ME_KEY = ['auth', 'me'] as const;

/**
 * مصدر الحقيقة الوحيد لحالة الدخول.
 * الحساب يُجلب من /me عند فتح التطبيق إذا وُجد توكن، فلا نثق بدور محفوظ في المتصفح.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [hasToken, setHasToken] = useState(() => tokenStorage.get() !== null);

  const meQuery = useQuery({
    queryKey: ME_KEY,
    queryFn: authApi.me,
    enabled: hasToken,
    staleTime: 5 * 60_000,
    retry: false,
  });

  const clearSession = useCallback(() => {
    tokenStorage.clear();
    setHasToken(false);
    queryClient.clear();
  }, [queryClient]);

  // أي رد 401 من الـ API يعني أن التوكن انتهى أو أُلغي
  useEffect(() => {
    setUnauthorizedHandler(clearSession);
    return () => setUnauthorizedHandler(null);
  }, [clearSession]);

  const startSession = useCallback(
    ({ access_token, account }: TokenPayload, remember = true): Account => {
      tokenStorage.set(access_token, remember);
      queryClient.setQueryData(ME_KEY, account);
      setHasToken(true);

      return account;
    },
    [queryClient],
  );

  const login = useCallback(
    async (input: LoginInput, remember: boolean): Promise<Account> => startSession(await authApi.login(input), remember),
    [startSession],
  );

  const refreshAccount = useCallback(async (): Promise<Account | null> => {
    if (!tokenStorage.get()) return null;

    const account = await authApi.me();
    queryClient.setQueryData(ME_KEY, account);

    return account;
  }, [queryClient]);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // حتى لو فشل الطلب (توكن منتهٍ مثلاً) نُنهي الجلسة محلياً
    } finally {
      clearSession();
    }
  }, [clearSession]);

  const value = useMemo<AuthContextValue>(() => {
    const account = hasToken ? (meQuery.data ?? null) : null;

    return {
      account,
      status: !hasToken || meQuery.isError ? 'guest' : account ? 'authenticated' : 'loading',
      login,
      startSession,
      refreshAccount,
      logout,
    };
  }, [hasToken, meQuery.data, meQuery.isError, login, startSession, refreshAccount, logout]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
