import type { ReactNode } from 'react';
import type { PaginationMeta } from '@/lib/api/types';
import { cn } from '@/lib/utils/cn';
import { Pagination } from './Pagination';
import { EmptyState, ErrorState, Skeleton } from './States';

export interface Column<T> {
  key: string;
  header: string;
  cell: (row: T) => ReactNode;
  className?: string;
  align?: 'start' | 'center' | 'end';
}

interface DataTableProps<T> {
  columns: Column<T>[];
  rows: T[] | undefined;
  rowKey: (row: T) => string | number;
  isLoading?: boolean;
  /** يظهر شريط خفيف أثناء جلب صفحة جديدة مع إبقاء الصفوف الحالية. */
  isFetching?: boolean;
  error?: unknown;
  onRetry?: () => void;
  meta?: PaginationMeta;
  onPageChange?: (page: number) => void;
  emptyTitle?: string;
  emptyDescription?: string;
}

const alignClass = { start: 'text-start', center: 'text-center', end: 'text-end' } as const;

/**
 * جدول البيانات الموحّد لكل صفحات الإدارة: تحميل، خطأ، فارغ، وترقيم صفحات في مكان واحد.
 */
export function DataTable<T>({
  columns,
  rows,
  rowKey,
  isLoading = false,
  isFetching = false,
  error,
  onRetry,
  meta,
  onPageChange,
  emptyTitle = 'لا توجد بيانات',
  emptyDescription,
}: DataTableProps<T>) {
  if (error) return <ErrorState error={error} onRetry={onRetry} />;

  const isEmpty = !isLoading && (rows?.length ?? 0) === 0;

  return (
    <div className="relative">
      {isFetching && !isLoading && (
        <div className="skeleton absolute inset-x-0 top-0 z-10 h-0.5" />
      )}

      <div className="overflow-x-auto">
        <table className="w-full min-w-[640px] border-collapse text-sm">
          <thead>
            <tr className="border-b border-line bg-brand-50/60 text-xs font-bold text-brand-700">
              {columns.map((column) => (
                <th
                  key={column.key}
                  scope="col"
                  className={cn('whitespace-nowrap px-4 py-3.5 first:rounded-ss-(--radius-card) last:rounded-se-(--radius-card)', alignClass[column.align ?? 'start'], column.className)}
                >
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="stagger divide-y divide-line/70">
            {isLoading
              ? Array.from({ length: 6 }, (_, index) => (
                  <tr key={index}>
                    {columns.map((column) => (
                      <td key={column.key} className="px-4 py-3.5">
                        <Skeleton className="h-4 w-full max-w-40" />
                      </td>
                    ))}
                  </tr>
                ))
              : rows?.map((row) => (
                  <tr key={rowKey(row)} className="transition-colors duration-200 hover:bg-accent-50/40">
                    {columns.map((column) => (
                      <td
                        key={column.key}
                        className={cn('px-4 py-3 align-middle', alignClass[column.align ?? 'start'], column.className)}
                      >
                        {column.cell(row)}
                      </td>
                    ))}
                  </tr>
                ))}
          </tbody>
        </table>
      </div>

      {isEmpty && <EmptyState title={emptyTitle} description={emptyDescription} />}

      {meta && onPageChange && !isEmpty && <Pagination meta={meta} onPageChange={onPageChange} />}
    </div>
  );
}
