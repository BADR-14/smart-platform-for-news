import { api } from '@/lib/api/client';
import type { ApiResponse, MessageResponse, PageParams, Paginated } from '@/lib/api/types';
import type { CategoryRef, Post, PostStatus, PostType, VerificationStatus } from '@/features/shared/types';

/* -------------------------------------------------------------------------- */
/*  Types                                                                      */
/* -------------------------------------------------------------------------- */

export interface CompanyOverview {
  posts: { total: number } & Record<PostStatus, number>;
  engagement: { views: number; likes: number; comments: number; shares: number };
  followers_count: number;
  featured_active_count: number;
  wallet: { balance: number; currency: string };
  active_plan: { id: number; name: string | null; posts_remaining: number; posts_count: number | null } | null;
}

export interface CompanyProfile {
  id: number;
  account_id: number;
  name: string;
  email: string | null;
  logo: string | null;
  phone: string | null;
  description: string | null;
  address: string | null;
  website_url: string | null;
  category: CategoryRef | null;
  company_registration_number: string | null;
  tax_id: string | null;
  verification_status: VerificationStatus;
  is_verified: boolean;
  posts_count: number;
  followers_count: number;
  created_at: string;
}

export interface CompanyProfileInput {
  name: string;
  description: string;
  website_url: string;
  category_id: number;
  address: string;
  phone: string;
  logo?: File | null;
}

export interface Wallet {
  uuid: string;
  name: string;
  balance: number;
  formatted_balance: string;
  currency: string;
  is_active: boolean;
}

export interface WalletOverview {
  wallet: Wallet;
  summary: { total_credited: number; total_debited: number };
}

export type TransactionKind = 'topup' | 'withdrawal' | 'plan_purchase' | 'featured_post';

export interface Transaction {
  uuid: string;
  type: 'credit' | 'debit' | 'gift';
  kind: TransactionKind | null;
  amount: number;
  currency: string;
  status: 'pending' | 'completed' | 'failed';
  description: string | null;
  created_at: string;
}

export interface TopUpIntent {
  client_secret: string;
  payment_intent_id: string;
  publishable_key: string | null;
  amount: number;
  currency: string;
}

export interface CompanyPlan {
  id: number;
  name: string;
  posts_count: number;
  price: number;
  price_per_post: number | null;
  is_active: boolean;
}

export interface CompanySubscription {
  id: number;
  plan_id: number;
  plan_name: string;
  posts_count: number;
  posts_remaining: number;
  status: 'active' | 'expired';
  price_paid: number;
  purchased_at: string | null;
}

export interface PlansOverview {
  plans: CompanyPlan[];
  active_plan: CompanySubscription | null;
  single_post_price: number;
  featured_duration_days: number;
  currency: string;
}

export interface FeatureOptions {
  price: number;
  duration_days: number;
  currency: string;
  wallet_balance: number;
  active_plan: { id: number; name: string | null; posts_remaining: number } | null;
}

export type FeaturePaymentMethod = 'wallet' | 'plan';

export interface CompanyFeaturedPost {
  id: number;
  post: { id: number; title: string; status: PostStatus } | null;
  cost: number;
  payment_method: FeaturePaymentMethod;
  status: 'queued' | 'active' | 'expired' | 'cancelled';
  starts_at: string | null;
  ends_at: string | null;
  created_at: string | null;
}

export interface MyPostsFilters extends PageParams {
  status?: PostStatus;
  search?: string;
}

export interface PostInput {
  title: string;
  description: string;
  post_type: PostType;
  category_id: number;
  is_online: boolean;
  location?: string;
  latitude?: number | null;
  longitude?: number | null;
  start_date?: string;
  end_date?: string;
  tags: string[];
  media: File[];
  remove_media_ids?: number[];
  /** عند التعديل يمكن حذفها لإبقاء الحالة كما هي. */
  action?: 'publish' | 'draft' | 'archive';
}

/* -------------------------------------------------------------------------- */
/*  Helpers                                                                    */
/* -------------------------------------------------------------------------- */

/** يحوّل بيانات المنشور إلى multipart (لأنها تحتوي ملفات). */
function toPostFormData(input: Partial<PostInput>, isUpdate: boolean): FormData {
  const form = new FormData();

  // PHP لا يقرأ ملفات طلب PATCH، فنرسل POST مع _method=PATCH
  if (isUpdate) form.append('_method', 'PATCH');

  const scalars: (keyof PostInput)[] = ['title', 'description', 'post_type', 'category_id', 'start_date', 'end_date', 'action'];

  for (const key of scalars) {
    const value = input[key];
    if (value !== undefined && value !== null && value !== '') form.append(key, String(value));
  }

  if (input.is_online !== undefined) {
    form.append('is_online', input.is_online ? '1' : '0');

    if (!input.is_online) {
      form.append('location', input.location ?? '');
      form.append('latitude', String(input.latitude ?? ''));
      form.append('longitude', String(input.longitude ?? ''));
    }
  }

  // مصفوفة فارغة تُرسل كحقل فارغ حتى تُمسح الوسوم عند التعديل
  if (input.tags) {
    if (input.tags.length === 0 && isUpdate) form.append('tags', '');
    input.tags.forEach((tag) => form.append('tags[]', tag));
  }

  input.media?.forEach((file) => form.append('media[]', file));
  input.remove_media_ids?.forEach((id) => form.append('remove_media_ids[]', String(id)));

  return form;
}

const message = (response: { data: MessageResponse }) => response.data.message;

/* -------------------------------------------------------------------------- */
/*  Calls                                                                      */
/* -------------------------------------------------------------------------- */

export const companyApi = {
  // ---- overview & profile ----
  async overview() {
    const { data } = await api.get<ApiResponse<CompanyOverview>>('/company/dashboard');
    return data.data;
  },

  async profile() {
    const { data } = await api.get<ApiResponse<CompanyProfile>>('/company/profile');
    return data.data;
  },

  async updateProfile(input: CompanyProfileInput) {
    const form = new FormData();

    form.append('name', input.name);
    form.append('description', input.description);
    form.append('website_url', input.website_url);
    form.append('category_id', String(input.category_id));
    form.append('address', input.address);
    form.append('phone', input.phone);
    if (input.logo) form.append('logo', input.logo);

    const { data } = await api.post<ApiResponse<CompanyProfile>>('/company/profile', form);
    return data;
  },

  // ---- posts ----
  async myPosts(params: MyPostsFilters) {
    const { data } = await api.get<Paginated<Post>>('/posts/my-posts', { params });
    return data;
  },

  async post(id: number) {
    const { data } = await api.get<ApiResponse<Post>>(`/posts/${id}`);
    return data.data;
  },

  async createPost(input: PostInput) {
    const { data } = await api.post<ApiResponse<Post>>('/posts', toPostFormData(input, false));
    return data;
  },

  async updatePost(id: number, input: Partial<PostInput>) {
    const { data } = await api.post<ApiResponse<Post>>(`/posts/${id}`, toPostFormData(input, true));
    return data;
  },

  deletePost: (id: number) => api.delete<MessageResponse>(`/posts/${id}`).then(message),

  // ---- featured ----
  async featureOptions() {
    const { data } = await api.get<ApiResponse<FeatureOptions>>('/company/featured/options');
    return data.data;
  },

  featurePost: (postId: number, payment_method: FeaturePaymentMethod) =>
    api.post<MessageResponse>(`/company/posts/${postId}/feature`, { payment_method }).then(message),

  async featuredPosts(params: PageParams) {
    const { data } = await api.get<Paginated<CompanyFeaturedPost>>('/company/featured', { params });
    return data;
  },

  // ---- wallet ----
  async wallet() {
    const { data } = await api.get<ApiResponse<WalletOverview>>('/company/wallet');
    return data.data;
  },

  async transactions(params: PageParams & { type?: Transaction['type'] }) {
    const { data } = await api.get<Paginated<Transaction>>('/company/wallet/transactions', { params });
    return data;
  },

  async initiateTopUp(amount: number) {
    const { data } = await api.post<ApiResponse<TopUpIntent>>('/company/wallet/topup', { amount });
    return data.data;
  },

  confirmTopUp: (payment_intent_id: string) =>
    api.post<MessageResponse>('/company/wallet/topup/confirm', { payment_intent_id }).then(message),

  withdraw: (amount: number, description?: string) =>
    api.post<MessageResponse>('/company/wallet/withdraw', { amount, description }).then(message),

  // ---- plans ----
  async plans() {
    const { data } = await api.get<ApiResponse<PlansOverview>>('/company/plans');
    return data.data;
  },

  subscribe: (planId: number) => api.post<MessageResponse>(`/company/plans/${planId}/subscribe`).then(message),

  async subscriptions(params: PageParams) {
    const { data } = await api.get<Paginated<CompanySubscription>>('/company/subscriptions', { params });
    return data;
  },
};
