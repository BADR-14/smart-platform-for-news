/** مفاتيح استعلامات لوحة الأدمن — كل ميزة لها جذر واحد يُبطَل بعد أي تعديل. */
export const adminKeys = {
  overview: ['admin', 'overview'] as const,
  analytics: ['admin', 'analytics'] as const,
  users: ['admin', 'users'] as const,
  companies: ['admin', 'companies'] as const,
  posts: ['admin', 'posts'] as const,
  reports: ['admin', 'reports'] as const,
  verification: ['admin', 'verification'] as const,
  plans: ['admin', 'plans'] as const,
  subscriptions: ['admin', 'subscriptions'] as const,
  featured: ['admin', 'featured'] as const,
  categories: ['admin', 'categories'] as const,
  tags: ['admin', 'tags'] as const,
};
