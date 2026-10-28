/** شكل الردود الموحّد في الـ API (انظر docs/API.md). */

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface MessageResponse {
  message: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export type Paginated<T, Extra extends object = object> = {
  data: T[];
  meta: PaginationMeta;
} & Extra;

export interface PageParams {
  page?: number;
  per_page?: number;
}
