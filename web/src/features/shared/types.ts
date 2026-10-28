/** أنواع الكيانات المشتركة بين أكثر من ميزة — مطابقة لـ Resources في الباك-إند. */

export type AccountType = 'user' | 'company' | 'admin';

export type VerificationStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected';

export interface Account {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  type: AccountType;
  verification_status: VerificationStatus;
  is_verified: boolean;
  is_active: boolean;
  email_verified: boolean;
  user_id: number | null;
  company_id: number | null;
  created_at: string;
}

export interface Author {
  id: number;
  name: string;
  avatar: string | null;
  type: AccountType;
  is_verified: boolean;
  company_id: number | null;
}

export interface Category {
  id: number;
  name: string;
  type: 'post' | 'company' | 'both';
}

export type CategoryRef = Pick<Category, 'id' | 'name'>;

export type PostStatus = 'draft' | 'published' | 'under_review' | 'rejected' | 'archived';

export type PostType = 'lecture' | 'exhibition' | 'event' | 'course';

export interface PostMedia {
  id: number;
  url: string;
  type: 'image' | 'video';
}

export interface Post {
  id: number;
  type: 'post';
  title: string;
  description: string | null;
  post_type: PostType;
  status: PostStatus;
  moderation_note?: string | null;
  is_online: boolean;
  location: string | null;
  latitude: number | null;
  longitude: number | null;
  start_date: string | null;
  end_date: string | null;
  category: CategoryRef | null;
  tags: { id: number; name: string }[];
  media: PostMedia[];
  cover_url: string | null;
  author: Author | null;
  views_count: number;
  likes_count: number;
  comments_count: number;
  shares_count: number;
  reviews_count: number;
  rating_avg: number | null;
  is_featured: boolean;
  featured_until: string | null;
  is_owner: boolean;
  created_at: string;
  updated_at: string;
}
