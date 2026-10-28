import { BadgeCheck, BarChart3, Building2, Flag, LayoutDashboard, Newspaper, Package, Settings, Tags, Users } from 'lucide-react';
import type { NavSection } from './navigation';

const icon = 'size-5';

/** قائمة الشريط الجانبي لـلوحة الإدارة — في ملف مستقل حتى لا تدخل في ملفات الموقع الآخر. */
export const adminNavigation: NavSection[] = [
  {
    items: [{ label: 'الرئيسية', to: '/admin', icon: <LayoutDashboard className={icon} />, end: true }],
  },
  {
    title: 'الإدارة',
    items: [
      { label: 'المستخدمون', to: '/admin/users', icon: <Users className={icon} /> },
      { label: 'الشركات', to: '/admin/companies', icon: <Building2 className={icon} /> },
      { label: 'المنشورات', to: '/admin/posts', icon: <Newspaper className={icon} /> },
      { label: 'طلبات التوثيق', to: '/admin/verification', icon: <BadgeCheck className={icon} /> },
      { label: 'البلاغات', to: '/admin/reports', icon: <Flag className={icon} /> },
    ],
  },
  {
    title: 'المنصة',
    items: [
      { label: 'الباقات والتمييز', to: '/admin/plans', icon: <Package className={icon} /> },
      { label: 'التصنيفات والوسوم', to: '/admin/categories', icon: <Tags className={icon} /> },
      { label: 'التقارير', to: '/admin/analytics', icon: <BarChart3 className={icon} /> },
      { label: 'الإعدادات', to: '/admin/settings', icon: <Settings className={icon} /> },
    ],
  },
];
