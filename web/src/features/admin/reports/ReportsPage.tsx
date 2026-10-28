import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { CheckCircle2, FileText, Flag, Hourglass, User, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import type { BadgeTone } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Select, Textarea } from '@/components/ui/FormField';
import { DetailRow, Tabs } from '@/components/ui/Misc';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { ReasonDialog } from '@/components/ui/ReasonDialog';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatCard } from '@/components/ui/StatCard';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact, useListParams } from '@/lib/hooks/useListParams';
import { formatDateTime, formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { Report, ReportAction, ReportFilters, ReportStatus } from '../api';
import { adminKeys } from '../keys';

const statusMeta: Record<ReportStatus, { label: string; tone: BadgeTone }> = {
  pending: { label: 'معلّق', tone: 'warning' },
  resolved: { label: 'تمت معالجته', tone: 'success' },
  dismissed: { label: 'مرفوض', tone: 'neutral' },
};

const actionLabels: Record<ReportAction, string> = {
  none: 'بدون إجراء على المحتوى',
  under_review: 'وضع المنشور قيد المراجعة',
  reject_post: 'رفض المنشور',
  delete_post: 'حذف المنشور',
  ban_account: 'حظر الحساب',
};

type StatusTab = ReportStatus | '';

export function ReportsPage() {
  const { page, search, filters, setPage, setSearch, setFilter } = useListParams(['status', 'type'] as const);
  const [resolving, setResolving] = useState<Report | null>(null);
  const [dismissing, setDismissing] = useState<Report | null>(null);
  const [viewing, setViewing] = useState<Report | null>(null);

  const params = compact({ page, search, ...filters }) as ReportFilters;

  const query = useQuery({
    queryKey: [...adminKeys.reports, params],
    queryFn: () => adminApi.reports(params),
    placeholderData: keepPreviousData,
  });

  const invalidate = [adminKeys.reports, adminKeys.overview, adminKeys.posts, adminKeys.users, adminKeys.companies];

  const dismiss = useActionMutation({
    mutationFn: ({ id, notes }: { id: number; notes: string }) => adminApi.dismissReport(id, notes || undefined),
    invalidate,
    onSuccess: () => setDismissing(null),
  });

  const columns: Column<Report>[] = [
    {
      key: 'target',
      header: 'المبلَّغ عنه',
      className: 'max-w-64',
      cell: (report) => (
        <div className="flex items-center gap-2.5">
          <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            {report.reportable_type === 'post' ? <FileText className="size-4" /> : <User className="size-4" />}
          </span>
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{report.reported_item}</p>
            <p className="text-xs text-muted">{report.reportable_type === 'post' ? 'منشور' : 'حساب'}</p>
          </div>
        </div>
      ),
    },
    { key: 'reason', header: 'السبب', className: 'max-w-56', cell: (report) => <p className="truncate text-ink-soft">{report.reason}</p> },
    { key: 'reporter', header: 'المبلِّغ', cell: (report) => report.reporter?.name ?? '—' },
    {
      key: 'status',
      header: 'الحالة',
      cell: (report) => <Badge tone={statusMeta[report.status].tone}>{statusMeta[report.status].label}</Badge>,
    },
    { key: 'created', header: 'التاريخ', cell: (report) => <span className="text-muted">{formatDateTime(report.created_at)}</span> },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (report) =>
        report.status === 'pending' ? (
          <div className="flex justify-end gap-1.5">
            <Button size="sm" onClick={() => setResolving(report)}>
              معالجة
            </Button>
            <Button size="sm" variant="outline" onClick={() => setDismissing(report)}>
              رفض البلاغ
            </Button>
          </div>
        ) : (
          <Button size="sm" variant="ghost" onClick={() => setViewing(report)}>
            التفاصيل
          </Button>
        ),
    },
  ];

  const stats = query.data?.stats;

  return (
    <>
      <PageHeader title="البلاغات" description="بلاغات المستخدمين على المنشورات والحسابات." />

      <section className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="كل البلاغات" value={formatNumber(stats?.total)} icon={<Flag className="size-5" />} loading={query.isLoading} />
        <StatCard label="معلّقة" value={formatNumber(stats?.pending)} icon={<Hourglass className="size-5" />} tone="warning" loading={query.isLoading} />
        <StatCard label="على منشورات" value={formatNumber(stats?.posts)} icon={<FileText className="size-5" />} tone="info" loading={query.isLoading} />
        <StatCard label="على حسابات" value={formatNumber(stats?.accounts)} icon={<User className="size-5" />} tone="accent" loading={query.isLoading} />
      </section>

      <Tabs<StatusTab>
        className="mb-4"
        value={filters.status as StatusTab}
        onChange={(value) => setFilter('status', value)}
        items={[
          { value: '', label: 'الكل', count: stats?.total },
          { value: 'pending', label: 'معلّقة', count: stats?.pending },
          { value: 'resolved', label: 'تمت معالجتها', count: stats?.resolved },
          { value: 'dismissed', label: 'مرفوضة', count: stats?.dismissed },
        ]}
      />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
          <SearchInput value={search} onChange={setSearch} placeholder="بحث بالسبب أو اسم المبلِّغ…" />
          <Select aria-label="النوع" wrapperClassName="w-full sm:w-44" value={filters.type} onChange={(event) => setFilter('type', event.target.value)}>
            <option value="">كل الأنواع</option>
            <option value="post">بلاغات المنشورات</option>
            <option value="account">بلاغات الحسابات</option>
          </Select>
        </div>

        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(report) => report.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد بلاغات"
        />
      </Card>

      {resolving && <ResolveReportDialog report={resolving} onClose={() => setResolving(null)} invalidate={invalidate} />}

      <ReasonDialog
        open={dismissing !== null}
        onClose={() => setDismissing(null)}
        tone="primary"
        required={false}
        title="رفض البلاغ"
        description="يُغلق البلاغ دون أي إجراء على المحتوى المبلَّغ عنه."
        label="ملاحظة (اختياري)"
        confirmLabel="رفض البلاغ"
        loading={dismiss.isPending}
        onSubmit={(notes) => dismissing && dismiss.mutate({ id: dismissing.id, notes })}
      />

      <Modal open={viewing !== null} onClose={() => setViewing(null)} title="تفاصيل البلاغ" size="sm">
        {viewing && (
          <dl className="divide-y divide-line">
            <DetailRow label="المبلَّغ عنه">{viewing.reported_item}</DetailRow>
            <DetailRow label="السبب">{viewing.reason}</DetailRow>
            <DetailRow label="الحالة">
              <Badge tone={statusMeta[viewing.status].tone}>{statusMeta[viewing.status].label}</Badge>
            </DetailRow>
            <DetailRow label="الإجراء المتخذ">{viewing.action_taken ? actionLabels[viewing.action_taken] : '—'}</DetailRow>
            <DetailRow label="ملاحظات الأدمن">{viewing.admin_notes ?? '—'}</DetailRow>
            <DetailRow label="عالجه">{viewing.resolved_by ?? '—'}</DetailRow>
            <DetailRow label="تاريخ المعالجة">{formatDateTime(viewing.resolved_at)}</DetailRow>
          </dl>
        )}
      </Modal>
    </>
  );
}

interface ResolveReportDialogProps {
  report: Report;
  onClose: () => void;
  invalidate: (readonly string[])[];
}

function ResolveReportDialog({ report, onClose, invalidate }: ResolveReportDialogProps) {
  const [action, setAction] = useState<ReportAction>('none');
  const [notes, setNotes] = useState('');

  const resolve = useActionMutation({
    mutationFn: () => adminApi.resolveReport(report.id, action, notes.trim() || undefined),
    invalidate,
    onSuccess: onClose,
  });

  const destructive = action === 'delete_post' || action === 'ban_account';

  return (
    <Modal
      open
      onClose={onClose}
      title="معالجة البلاغ"
      size="md"
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={resolve.isPending}>
            إلغاء
          </Button>
          <Button
            variant={destructive ? 'danger' : 'primary'}
            icon={destructive ? <XCircle className="size-4" /> : <CheckCircle2 className="size-4" />}
            loading={resolve.isPending}
            onClick={() => resolve.mutate()}
          >
            تأكيد المعالجة
          </Button>
        </>
      }
    >
      <div className="flex flex-col gap-4">
        <dl className="divide-y divide-line rounded-xl bg-canvas px-4">
          <DetailRow label={report.reportable_type === 'post' ? 'المنشور' : 'الحساب'}>{report.reported_item}</DetailRow>
          <DetailRow label="سبب البلاغ">{report.reason}</DetailRow>
          <DetailRow label="المبلِّغ">{report.reporter?.name ?? '—'}</DetailRow>
        </dl>

        <Select label="الإجراء" value={action} onChange={(event) => setAction(event.target.value as ReportAction)}>
          {report.available_actions.map((value) => (
            <option key={value} value={value}>
              {actionLabels[value]}
            </option>
          ))}
        </Select>

        <Textarea
          label="ملاحظات"
          hint={
            report.reportable_type === 'post'
              ? 'تظهر الملاحظة لصاحب المنشور عند وضعه قيد المراجعة أو رفضه.'
              : 'تُستخدم كسبب للحظر عند اختيار حظر الحساب.'
          }
          rows={3}
          maxLength={255}
          value={notes}
          onChange={(event) => setNotes(event.target.value)}
        />
      </div>
    </Modal>
  );
}
