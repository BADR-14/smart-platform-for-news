import { CreditCard, FileText, LayoutDashboard, Settings, Sparkles, Store, Wallet } from 'lucide-react';
import type { NavSection } from './navigation';

const icon = 'size-5';

/** قائمة الشريط الجانبي لـلوحة الشركة — في ملف مستقل حتى لا تدخل في ملفات الموقع الآخر. */
export const companyNavigation: NavSection[] = [
  {
    items: [{ label: 'الرئيسية', to: '/company', icon: <LayoutDashboard className={icon} />, end: true }],
  },
  {
    title: 'المحتوى',
    items: [
      { label: 'منشوراتي', to: '/company/posts', icon: <FileText className={icon} /> },
      { label: 'المنشورات المميزة', to: '/company/featured', icon: <Sparkles className={icon} /> },
    ],
  },
  {
    title: 'المالية',
    items: [
      { label: 'المحفظة', to: '/company/wallet', icon: <Wallet className={icon} /> },
      { label: 'الباقات', to: '/company/plans', icon: <CreditCard className={icon} /> },
    ],
  },
  {
    title: 'الحساب',
    items: [
      { label: 'ملف الشركة', to: '/company/profile', icon: <Store className={icon} /> },
      { label: 'الإعدادات', to: '/company/settings', icon: <Settings className={icon} /> },
    ],
  },
];
