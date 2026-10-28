import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';
import type { ApiResponse } from '@/lib/api/types';
import type { Category } from './types';

export const categoriesApi = {
  async list(type?: 'post' | 'company'): Promise<Category[]> {
    const { data } = await api.get<ApiResponse<Category[]>>('/categories', { params: { type } });
    return data.data;
  },

  async searchTags(search: string): Promise<{ id: number; name: string }[]> {
    const { data } = await api.get<ApiResponse<{ id: number; name: string }[]>>('/tags', { params: { search } });
    return data.data;
  },
};

/** التصنيفات تتغير نادراً، فتُخزَّن 10 دقائق. */
export function useCategories(type?: 'post' | 'company') {
  return useQuery({
    queryKey: ['categories', type ?? 'all'],
    queryFn: () => categoriesApi.list(type),
    staleTime: 10 * 60_000,
  });
}
