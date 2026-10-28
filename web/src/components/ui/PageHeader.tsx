import type { ReactNode } from 'react';
import { isAdminSite } from '@/config/env';

interface PageHeaderProps {
  title: string;
  description?: string;
  actions?: ReactNode;
}

export function PageHeader({ title, description, actions }: PageHeaderProps) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div className="min-w-0">
        <h1 className="flex items-center gap-3 text-2xl font-bold text-ink">
          {/* لوحة الإدارة عناوينها بلا زخرفة؛ الشريط البرتقالي من هوية لوحة الشركة */}
          {!isAdminSite && <span aria-hidden className="bg-accent-gradient h-7 w-1.5 rounded-full" />}
          {title}
        </h1>
        {description && <p className={isAdminSite ? 'mt-1 text-sm text-muted' : 'mt-1.5 ps-[1.125rem] text-sm text-muted'}>{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}
