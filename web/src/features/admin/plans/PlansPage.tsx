import { useQuery } from '@tanstack/react-query';
import { CircleDollarSign, Package, PackageCheck, Plus, Users } from 'lucide-react';
import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Tabs } from '@/components/ui/Misc';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ErrorState } from '@/components/ui/States';
import { formatMoney, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { Plan } from '../api';
import { adminKeys } from '../keys';
import { FeaturedPostsTable } from './FeaturedPostsTable';
import { FeaturedSettingsCard } from './FeaturedSettingsCard';
import { PlanFormDialog } from './PlanFormDialog';
import { PlansTable } from './PlansTable';
import { SubscriptionsTable } from './SubscriptionsTable';

type Tab = 'plans' | 'subscriptions' | 'featured';

export function PlansPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const tab = (searchParams.get('tab') as Tab | null) ?? 'plans';
  const [editing, setEditing] = useState<Plan | 'new' | null>(null);

  const query = useQuery({ queryKey: adminKeys.plans, queryFn: adminApi.plans });

  if (query.error) return <ErrorState error={query.error} onRetry={() => query.refetch()} />;

  const stats = query.data?.stats;
  const icon = 'size-5';

  return (
    <>
      <PageHeader
        title="الباقات والتمييز"
        description="الباقات التي تشتريها الشركات، وسعر ومدة المنشور المميز."
        actions={
          tab === 'plans' && (
            <Button icon={<Plus className="size-4" />} onClick={() => setEditing('new')}>
              باقة جديدة
            </Button>
          )
        }
      />

      <section className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="الباقات" value={formatNumber(stats?.total_plans)} icon={<Package className={icon} />} loading={query.isLoading} />
        <StatCard label="باقات مفعّلة" value={formatNumber(stats?.active_plans)} icon={<PackageCheck className={icon} />} tone="success" loading={query.isLoading} />
        <StatCard label="اشتراكات نشطة" value={formatNumber(stats?.active_subscriptions)} hint={stats ? `من أصل ${formatNumber(stats.total_subscriptions)} اشتراك` : undefined} icon={<Users className={icon} />} tone="info" loading={query.isLoading} />
        <StatCard label="إيرادات الباقات" value={formatMoney(stats?.total_payments)} icon={<CircleDollarSign className={icon} />} tone="accent" loading={query.isLoading} />
      </section>

      <Tabs<Tab>
        className="mb-4"
        value={tab}
        onChange={(value) => setSearchParams(value === 'plans' ? {} : { tab: value }, { replace: true })}
        items={[
          { value: 'plans', label: 'الباقات' },
          { value: 'subscriptions', label: 'اشتراكات الشركات' },
          { value: 'featured', label: 'المنشورات المميزة' },
        ]}
      />

      {tab === 'plans' && (
        <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <PlansTable plans={query.data?.plans} isLoading={query.isLoading} singlePostPrice={query.data?.settings.featured_post_price} onEdit={setEditing} />
          {query.data && <FeaturedSettingsCard settings={query.data.settings} />}
        </div>
      )}

      {tab === 'subscriptions' && <SubscriptionsTable />}
      {tab === 'featured' && <FeaturedPostsTable />}

      {editing && <PlanFormDialog plan={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}
    </>
  );
}
