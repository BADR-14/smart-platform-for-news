import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from '@/app/App';
import './index.css';

/**
 * نقطة البداية المشتركة للموقعين.
 * الشرط على `import.meta.env.MODE` يُحسم وقت البناء، فيدخل في كل موقع مساراته فقط
 * ويُحذف استيراد مسارات الموقع الآخر من الملفات الناتجة.
 */
const { router } =
  import.meta.env.MODE === 'admin' ? await import('@/app/admin-router') : await import('@/app/company-router');

// سمة كل موقع (ألوان الأسطح والزوايا) تُختار من هذه الخاصية في index.css
document.documentElement.dataset.site = import.meta.env.MODE === 'admin' ? 'admin' : 'company';

document.title =
  import.meta.env.MODE === 'admin' ? 'لوحة الإدارة — Smart Platform for News' : 'Smart Platform for News — للشركات';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App router={router} />
  </StrictMode>,
);
