/** مفاتيح استعلامات لوحة الشركة. */
export const companyKeys = {
  overview: ['company', 'overview'] as const,
  profile: ['company', 'profile'] as const,
  posts: ['company', 'posts'] as const,
  post: (id: number) => ['company', 'posts', 'detail', id] as const,
  featured: ['company', 'featured'] as const,
  featureOptions: ['company', 'feature-options'] as const,
  wallet: ['company', 'wallet'] as const,
  transactions: ['company', 'transactions'] as const,
  plans: ['company', 'plans'] as const,
  subscriptions: ['company', 'subscriptions'] as const,
};

/** كل ما يتأثر بأي عملية مالية (شحن، سحب، شراء باقة، تمييز منشور). */
export const billingKeys = [
  companyKeys.overview,
  companyKeys.wallet,
  companyKeys.transactions,
  companyKeys.plans,
  companyKeys.subscriptions,
  companyKeys.featured,
  companyKeys.featureOptions,
  companyKeys.posts,
];
