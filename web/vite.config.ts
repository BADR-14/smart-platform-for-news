import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

/**
 * موقعان مستقلان من نفس الكود، يُختار بينهما بالـ mode:
 *   vite --mode company  ⟵ موقع الشركات  (المنفذ 5173، البناء في dist/company)
 *   vite --mode admin    ⟵ موقع الإدارة   (المنفذ 5174، البناء في dist/admin)
 *
 * كل موقع يُبنى بمساراته وصفحاته فقط: كود لوحة الإدارة لا يدخل إطلاقاً في ملفات موقع الشركات.
 */
export default defineConfig(({ mode }) => {
  const isAdmin = mode === 'admin';

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    // كاش منفصل لكل موقع حتى يعمل الخادمان معاً دون تعارض
    cacheDir: `node_modules/.vite-${isAdmin ? 'admin' : 'company'}`,
    server: {
      port: isAdmin ? 5174 : 5173,
      strictPort: true,
    },
    preview: {
      port: isAdmin ? 4174 : 4173,
    },
    build: {
      outDir: isAdmin ? 'dist/admin' : 'dist/company',
      rollupOptions: {
        output: {
          // فصل المكتبات الكبيرة عن كود التطبيق حتى تبقى مخزّنة في المتصفح بين الإصدارات
          manualChunks: {
            react: ['react', 'react-dom', 'react-router-dom'],
            data: ['@tanstack/react-query', 'axios'],
            forms: ['react-hook-form', '@hookform/resolvers', 'zod'],
            charts: ['recharts'],
          },
        },
      },
    },
  };
});
