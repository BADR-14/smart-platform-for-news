import { QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router-dom';
import type { createBrowserRouter } from 'react-router-dom';
import { Toaster } from 'sonner';
import { AuthProvider } from '@/features/auth/AuthProvider';
import { queryClient } from '@/lib/query-client';

type AppRouter = ReturnType<typeof createBrowserRouter>;

/** جذر التطبيق. المسارات تُمرَّر من main.tsx حسب الموقع (شركات أو إدارة). */
export function App({ router }: { router: AppRouter }) {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <RouterProvider router={router} />
        <Toaster
          dir="rtl"
          position="top-center"
          richColors
          closeButton
          toastOptions={{ style: { fontFamily: 'var(--font-sans)' } }}
        />
      </AuthProvider>
    </QueryClientProvider>
  );
}
