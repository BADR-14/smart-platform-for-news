import type { ReactNode } from 'react';

export interface NavItem {
  label: string;
  to: string;
  icon: ReactNode;
  /** المطابقة التامة للمسار (للصفحة الرئيسية للوحة). */
  end?: boolean;
}

export interface NavSection {
  title?: string;
  items: NavItem[];
}
