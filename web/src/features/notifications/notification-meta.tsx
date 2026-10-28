import {
  BadgeCheck,
  Ban,
  Bell,
  Building2,
  Flag,
  Heart,
  LogIn,
  MessageCircle,
  ShieldAlert,
  Star,
  Trash2,
  UserPlus,
} from 'lucide-react';
import type { ReactNode } from 'react';
import type { AccountType } from '@/features/shared/types';
import type { AppNotification } from './api';

interface NotificationVisual {
  icon: ReactNode;
  className: string;
}

const visuals: Record<string, NotificationVisual> = {
  new_user_registered: { icon: <UserPlus className="size-4" />, className: 'bg-info-50 text-info-600' },
  new_company_registered: { icon: <Building2 className="size-4" />, className: 'bg-accent-50 text-accent-700' },
  report_created: { icon: <Flag className="size-4" />, className: 'bg-danger-50 text-danger-600' },
  verification_requested: { icon: <BadgeCheck className="size-4" />, className: 'bg-warning-50 text-warning-600' },
  verification_reviewed: { icon: <BadgeCheck className="size-4" />, className: 'bg-success-50 text-success-600' },
  post_commented: { icon: <MessageCircle className="size-4" />, className: 'bg-info-50 text-info-600' },
  comment_reply: { icon: <MessageCircle className="size-4" />, className: 'bg-info-50 text-info-600' },
  post_reacted: { icon: <Heart className="size-4" />, className: 'bg-danger-50 text-danger-600' },
  post_reviewed: { icon: <Star className="size-4" />, className: 'bg-warning-50 text-warning-600' },
  new_follower: { icon: <UserPlus className="size-4" />, className: 'bg-success-50 text-success-600' },
  post_moderated: { icon: <ShieldAlert className="size-4" />, className: 'bg-warning-50 text-warning-600' },
  post_deleted: { icon: <Trash2 className="size-4" />, className: 'bg-danger-50 text-danger-600' },
  account_banned: { icon: <Ban className="size-4" />, className: 'bg-danger-50 text-danger-600' },
  login_success: { icon: <LogIn className="size-4" />, className: 'bg-brand-50 text-brand-600' },
};

export function notificationVisual(type: string): NotificationVisual {
  return visuals[type] ?? { icon: <Bell className="size-4" />, className: 'bg-brand-50 text-brand-600' };
}

/** الصفحة التي يفتحها الضغط على الإشعار (حسب نوعه ونوع الحساب). */
export function notificationLink(notification: AppNotification, role: AccountType): string | null {
  if (role === 'admin') {
    switch (notification.type) {
      case 'new_user_registered':
        return '/admin/users';
      case 'new_company_registered':
      case 'verification_requested':
        return '/admin/verification';
      case 'report_created':
        return '/admin/reports';
      default:
        return null;
    }
  }

  if (role === 'company') {
    switch (notification.type) {
      case 'post_commented':
      case 'comment_reply':
      case 'post_reacted':
      case 'post_reviewed':
      case 'post_moderated':
      case 'post_deleted':
        return '/company/posts';
      default:
        return null;
    }
  }

  return null;
}
