import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';

/**
 * حالة القوائم (الصفحة، البحث، الفلاتر) محفوظة في عنوان الصفحة:
 * تبقى بعد التحديث، ويمكن مشاركة الرابط، وزر الرجوع يعيد الفلاتر السابقة.
 */
export function useListParams<TFilter extends string>(filterKeys: readonly TFilter[]) {
  const [searchParams, setSearchParams] = useSearchParams();

  const page = Math.max(1, Number(searchParams.get('page')) || 1);
  const search = searchParams.get('search') ?? '';

  const filters = useMemo(() => {
    const result = {} as Record<TFilter, string>;
    for (const key of filterKeys) result[key] = searchParams.get(key) ?? '';
    return result;
    // filterKeys ثابتة لكل صفحة
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams]);

  const update = useCallback(
    (changes: Record<string, string | number | null | undefined>, resetPage = true) => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);

          if (resetPage) next.delete('page');

          for (const [key, value] of Object.entries(changes)) {
            if (value === null || value === undefined || value === '') next.delete(key);
            else next.set(key, String(value));
          }

          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const setPage = useCallback((value: number) => update({ page: value > 1 ? value : null }, false), [update]);
  const setSearch = useCallback((value: string) => update({ search: value }), [update]);
  const setFilter = useCallback((key: TFilter, value: string) => update({ [key]: value }), [update]);

  return { page, search, filters, setPage, setSearch, setFilter };
}

/** يحذف القيم الفارغة حتى لا تُرسل كـ query params. */
export function compact<T extends Record<string, unknown>>(params: T): Partial<T> {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
  ) as Partial<T>;
}
