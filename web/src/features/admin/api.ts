import { api } from '@/lib/api/client';
import type { ApiResponse, MessageResponse, PageParams, Paginated } from '@/lib/api/types';
import type { AccountType, Category, CategoryRef, PostStatus, PostType, VerificationStatus } from '@/features/shared/types';

/* -------------------------------------------------------------------------- */
/*  Overview & analytics                                                       */
/* -------------------------------------------------------------------------- */

export interface OverviewStatistics {
  users: number;
  companies: number;
  posts: number;
  published_posts: number;
  under_review_posts: number;
  pending_reports: number;
  pending_verifications: number;
  plans: number;
  featured_posts: number;
  wallets_balance: number;
}

export interface GrowthCharts {
  year: number;
  labels: string[];
  users_growth: number[];
  news_growth: number[];
  companies_growth: number[];
}

export interface MonthlyRecord {
  year: number;
  month_number: number;
  month_name: string;
  users_count: number;
  companies_count: number;
  news_count: number;
  reports_count: number;
}

export interface Analytics {
  counters: { total_users: number; total_companies: number; total_news: number; total_reports: number };
  charts: GrowthCharts;
  available_years: number[];
  monthly_reports: { records: MonthlyRecord[]; meta: Paginated<never>['meta'] };
}

/* -------------------------------------------------------------------------- */
/*  Users & companies                                                          */
/* -------------------------------------------------------------------------- */

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  account_type: AccountType;
  verification_status: VerificationStatus;
  is_active: boolean;
  ban_reason: string | null;
  email_verified: boolean;
  phone_number: string | null;
  posts_count: number;
  created_at: string;
}

export interface UserFilters extends PageParams {
  search?: string;
  status?: 'active' | 'blocked';
  verification?: VerificationStatus;
}

export interface AdminCompany {
  id: number;
  account_id: number;
  name: string;
  email: string | null;
  logo: string | null;
  category: CategoryRef | null;
  is_active: boolean;
  ban_reason: string | null;
  wallet_balance: number;
  posts_count: number;
  followers_count: number;
  active_plan: { name: string | null; posts_remaining: number } | null;
  created_at: string;
}

export interface CompanyFilters extends PageParams {
  search?: string;
  category_id?: number;
  status?: 'active' | 'blocked';
}

/* -------------------------------------------------------------------------- */
/*  Posts                                                                      */
/* -------------------------------------------------------------------------- */

export interface AdminPost {
  id: number;
  title: string;
  post_type: PostType;
  status: PostStatus;
  moderation_note: string | null;
  author: { id: number; name: string; type: AccountType } | null;
  category: string | null;
  views_count: number;
  likes_count: number;
  comments_count: number;
  pending_reports_count: number;
  is_featured: boolean;
  start_date: string | null;
  end_date: string | null;
  created_at: string;
}

export interface PostStats {
  total_news: number;
  published: number;
  under_review: number;
  archived: number;
  rejected: number;
  draft: number;
}

export interface PostFilters extends PageParams {
  search?: string;
  status?: PostStatus;
  category_id?: number;
  date_filter?: 'last_7' | 'last_30' | 'last_90';
}

export type ModerationStatus = Exclude<PostStatus, 'draft'>;

/* -------------------------------------------------------------------------- */
/*  Reports                                                                    */
/* -------------------------------------------------------------------------- */

export type ReportStatus = 'pending' | 'resolved' | 'dismissed';
export type ReportableType = 'post' | 'account';
export type ReportAction = 'none' | 'under_review' | 'reject_post' | 'delete_post' | 'ban_account';

export interface Report {
  id: number;
  reportable_type: ReportableType;
  reportable_id: number;
  reported_item: string;
  reported_item_status: string;
  reporter: { id: number; name: string } | null;
  reason: string;
  status: ReportStatus;
  action_taken: ReportAction | null;
  available_actions: ReportAction[];
  admin_notes: string | null;
  resolved_by: string | null;
  resolved_at: string | null;
  created_at: string;
}

export interface ReportStats {
  total: number;
  pending: number;
  resolved: number;
  dismissed: number;
  posts: number;
  accounts: number;
}

export interface ReportFilters extends PageParams {
  search?: string;
  status?: ReportStatus;
  type?: ReportableType;
}

/* -------------------------------------------------------------------------- */
/*  Verification                                                               */
/* -------------------------------------------------------------------------- */

export type VerificationRequestStatus = 'pending' | 'approved' | 'rejected';

export interface VerificationDocument {
  id: number;
  document_type: 'identity_front' | 'identity_back' | 'commercial_register' | 'tax_document';
  file_url: string;
  is_image: boolean;
  extracted_text: string | null;
}

export interface VerificationRequest {
  id: number;
  type: 'user' | 'company';
  status: VerificationRequestStatus;
  confidence_score: number | null;
  rejection_reasons: string[];
  submitted_at: string;
  reviewed_at: string | null;
  account: {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    account_type: AccountType;
    national_id: string | null;
    linkedin_url: string | null;
  } | null;
  company: {
    id: number;
    name: string;
    logo: string | null;
    website_url: string | null;
    category: string | null;
    phone: string | null;
    address: string | null;
    description: string | null;
    company_registration_number: string | null;
    tax_id: string | null;
  } | null;
  reviewed_by: { name: string | null } | null;
  documents: VerificationDocument[];
}

export interface VerificationStats {
  pending: number;
  approved: number;
  rejected: number;
  total: number;
}

export interface VerificationFilters extends PageParams {
  status?: VerificationRequestStatus;
  type?: 'user' | 'company';
  search?: string;
}

export interface ReviewInput {
  decision: 'approved' | 'rejected';
  rejection_reasons?: string[];
}

/* -------------------------------------------------------------------------- */
/*  Plans, subscriptions, featured                                             */
/* -------------------------------------------------------------------------- */

export interface Plan {
  id: number;
  name: string;
  posts_count: number;
  price: number;
  price_per_post: number | null;
  is_active: boolean;
  subscriptions_count?: number;
  created_at: string | null;
}

export interface PlanInput {
  name: string;
  posts_count: number;
  price: number;
  is_active: boolean;
}

export interface PlatformSettings {
  featured_post_price: number;
  featured_post_duration_days: number;
}

export interface PlansOverview {
  plans: Plan[];
  stats: {
    total_plans: number;
    active_plans: number;
    total_subscriptions: number;
    active_subscriptions: number;
    total_payments: number;
  };
  settings: PlatformSettings;
}

export interface Subscription {
  id: number;
  company_id: number;
  company_name?: string;
  plan_id: number;
  plan_name: string;
  posts_count: number;
  posts_remaining: number;
  status: 'active' | 'expired';
  price_paid: number;
  purchased_at: string | null;
}

export interface FeaturedPost {
  id: number;
  post: { id: number; title: string; status: PostStatus } | null;
  company?: { id: number; name: string } | null;
  cost: number;
  payment_method: 'wallet' | 'plan';
  status: 'queued' | 'active' | 'expired' | 'cancelled';
  starts_at: string | null;
  ends_at: string | null;
  created_at: string | null;
}

/* -------------------------------------------------------------------------- */
/*  Categories & tags                                                          */
/* -------------------------------------------------------------------------- */

export interface AdminCategory extends Category {
  posts_count: number;
  companies_count: number;
}

export interface AdminTag {
  id: number;
  name: string;
  posts_count: number;
}

/* -------------------------------------------------------------------------- */
/*  Calls                                                                      */
/* -------------------------------------------------------------------------- */

const message = (response: { data: MessageResponse }) => response.data.message;

export const adminApi = {
  // ---- overview ----
  async overview() {
    const { data } = await api.get<ApiResponse<{ statistics: OverviewStatistics; charts: GrowthCharts }>>('/admin/overview');
    return data.data;
  },

  async analytics(params: { year?: number; month?: number; page?: number }) {
    const { data } = await api.get<ApiResponse<Analytics>>('/admin/analytics', { params });
    return data.data;
  },

  // ---- users ----
  async users(params: UserFilters) {
    const { data } = await api.get<Paginated<AdminUser, { stats: { total: number; verified: number; blocked: number } }>>(
      '/admin/users',
      { params },
    );
    return data;
  },
  banUser: (id: number, ban_reason: string) => api.post<MessageResponse>(`/admin/users/${id}/ban`, { ban_reason }).then(message),
  unbanUser: (id: number) => api.post<MessageResponse>(`/admin/users/${id}/unban`).then(message),
  deleteUser: (id: number) => api.delete<MessageResponse>(`/admin/users/${id}`).then(message),

  // ---- companies ----
  async companies(params: CompanyFilters) {
    const { data } = await api.get<
      Paginated<AdminCompany, { stats: { total_companies: number; blocked_companies: number; total_balance: number } }>
    >('/admin/companies', { params });
    return data;
  },
  banCompany: (id: number, ban_reason: string) =>
    api.post<MessageResponse>(`/admin/companies/${id}/ban`, { ban_reason }).then(message),
  unbanCompany: (id: number) => api.post<MessageResponse>(`/admin/companies/${id}/unban`).then(message),

  // ---- posts ----
  async posts(params: PostFilters) {
    const { data } = await api.get<Paginated<AdminPost, { stats: PostStats }>>('/admin/posts', { params });
    return data;
  },
  updatePostStatus: (id: number, status: ModerationStatus, note?: string) =>
    api.patch<MessageResponse>(`/admin/posts/${id}/status`, { status, note }).then(message),
  deletePost: (id: number) => api.delete<MessageResponse>(`/admin/posts/${id}`).then(message),

  // ---- reports ----
  async reports(params: ReportFilters) {
    const { data } = await api.get<Paginated<Report, { stats: ReportStats }>>('/admin/reports', { params });
    return data;
  },
  resolveReport: (id: number, action: ReportAction, admin_notes?: string) =>
    api.patch<MessageResponse>(`/admin/reports/${id}/resolve`, { action, admin_notes }).then(message),
  dismissReport: (id: number, admin_notes?: string) =>
    api.patch<MessageResponse>(`/admin/reports/${id}/dismiss`, { admin_notes }).then(message),

  // ---- verification ----
  async verificationRequests(params: VerificationFilters) {
    const { data } = await api.get<Paginated<VerificationRequest, { stats: VerificationStats }>>('/admin/verification', {
      params,
    });
    return data;
  },
  reviewVerification: (id: number, input: ReviewInput) =>
    api.post<MessageResponse>(`/admin/verification/${id}/review`, input).then(message),

  // ---- plans ----
  async plans() {
    const { data } = await api.get<ApiResponse<PlansOverview>>('/admin/plans');
    return data.data;
  },
  createPlan: (input: PlanInput) => api.post<MessageResponse>('/admin/plans', input).then(message),
  updatePlan: (id: number, input: PlanInput) => api.put<MessageResponse>(`/admin/plans/${id}`, input).then(message),
  togglePlan: (id: number) => api.patch<MessageResponse>(`/admin/plans/${id}/toggle-status`).then(message),
  deletePlan: (id: number) => api.delete<MessageResponse>(`/admin/plans/${id}`).then(message),
  updateSettings: (input: PlatformSettings) => api.patch<MessageResponse>('/admin/platform-settings', input).then(message),

  async subscriptions(params: PageParams & { search?: string; status?: 'active' | 'expired' }) {
    const { data } = await api.get<Paginated<Subscription>>('/admin/subscriptions', { params });
    return data;
  },

  async featuredPosts(params: PageParams & { status?: FeaturedPost['status'] }) {
    const { data } = await api.get<Paginated<FeaturedPost>>('/admin/featured-posts', { params });
    return data;
  },

  // ---- categories & tags ----
  async categories() {
    const { data } = await api.get<ApiResponse<AdminCategory[]>>('/admin/categories');
    return data.data;
  },
  createCategory: (input: Pick<Category, 'name' | 'type'>) => api.post<MessageResponse>('/admin/categories', input).then(message),
  updateCategory: (id: number, input: Pick<Category, 'name' | 'type'>) =>
    api.put<MessageResponse>(`/admin/categories/${id}`, input).then(message),
  deleteCategory: (id: number) => api.delete<MessageResponse>(`/admin/categories/${id}`).then(message),

  async tags(params: PageParams & { search?: string }) {
    const { data } = await api.get<Paginated<AdminTag>>('/admin/tags', { params });
    return data;
  },
  deleteTag: (id: number) => api.delete<MessageResponse>(`/admin/tags/${id}`).then(message),
};
