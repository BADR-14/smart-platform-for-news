import { zodResolver } from '@hookform/resolvers/zod';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable } from '@/components/ui/DataTable';
import type { Column } from '@/components/ui/DataTable';
import { Input, Select } from '@/components/ui/FormField';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { SearchInput } from '@/components/ui/SearchInput';
import type { Category } from '@/features/shared/types';
import { useActionMutation } from '@/lib/hooks/useActionMutation';
import { compact } from '@/lib/hooks/useListParams';
import { applyServerErrors } from '@/lib/utils/form';
import { formatNumber } from '@/lib/utils/format';
import { adminApi } from '../api';
import type { AdminCategory, AdminTag } from '../api';
import { adminKeys } from '../keys';

const typeLabels: Record<Category['type'], string> = {
  post: 'للمنشورات',
  company: 'للشركات',
  both: 'للمنشورات والشركات',
};

// التصنيفات العامة (القوائم المنسدلة) تُحدَّث أيضاً بعد أي تعديل
const categoryInvalidation = [adminKeys.categories, ['categories']];

export function CategoriesPage() {
  const [editing, setEditing] = useState<AdminCategory | 'new' | null>(null);
  const [deleting, setDeleting] = useState<AdminCategory | null>(null);

  const query = useQuery({ queryKey: adminKeys.categories, queryFn: adminApi.categories });

  const remove = useActionMutation({
    mutationFn: adminApi.deleteCategory,
    invalidate: categoryInvalidation,
    onSuccess: () => setDeleting(null),
  });

  const columns: Column<AdminCategory>[] = [
    { key: 'name', header: 'التصنيف', cell: (category) => <span className="font-medium text-ink">{category.name}</span> },
    { key: 'type', header: 'يُستخدم', cell: (category) => <Badge tone="brand">{typeLabels[category.type]}</Badge> },
    { key: 'posts', header: 'المنشورات', align: 'center', cell: (category) => formatNumber(category.posts_count) },
    { key: 'companies', header: 'الشركات', align: 'center', cell: (category) => formatNumber(category.companies_count) },
    {
      key: 'actions',
      header: 'إجراءات',
      align: 'end',
      cell: (category) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="icon" aria-label="تعديل التصنيف" onClick={() => setEditing(category)}>
            <Pencil className="size-4" />
          </Button>
          <Button variant="ghost" size="icon" aria-label="حذف التصنيف" onClick={() => setDeleting(category)}>
            <Trash2 className="size-4 text-danger-600" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="التصنيفات والوسوم"
        description="التصنيفات يختارها الناشر عند إنشاء منشور أو شركة. الوسوم يكتبها الناشرون بحرية."
        actions={
          <Button icon={<Plus className="size-4" />} onClick={() => setEditing('new')}>
            تصنيف جديد
          </Button>
        }
      />

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <Card className="h-fit">
          <CardHeader title="التصنيفات" />
          <DataTable
            columns={columns}
            rows={query.data}
            rowKey={(category) => category.id}
            isLoading={query.isLoading}
            error={query.error}
            onRetry={() => query.refetch()}
            emptyTitle="لا توجد تصنيفات"
          />
        </Card>

        <TagsCard />
      </div>

      {editing && <CategoryFormDialog category={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}

      <ConfirmDialog
        open={deleting !== null}
        onClose={() => setDeleting(null)}
        title="حذف التصنيف"
        message={
          <>
            هل تريد حذف <strong>{deleting?.name}</strong>؟ التصنيف المستخدم في منشورات أو شركات لا يُحذف.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  );
}

// ==================== نموذج التصنيف ====================

const schema = z.object({
  name: z.string().trim().min(1, 'اسم التصنيف مطلوب').max(100),
  type: z.enum(['post', 'company', 'both']),
});

type FormValues = z.infer<typeof schema>;

function CategoryFormDialog({ category, onClose }: { category: AdminCategory | null; onClose: () => void }) {
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: category?.name ?? '', type: category?.type ?? 'both' },
  });

  const save = useActionMutation({
    mutationFn: (values: FormValues) => (category ? adminApi.updateCategory(category.id, values) : adminApi.createCategory(values)),
    invalidate: categoryInvalidation,
    errorToast: false,
    onSuccess: onClose,
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      await save.mutateAsync(values);
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['name', 'type']));
    }
  });

  return (
    <Modal
      open
      onClose={onClose}
      size="sm"
      title={category ? 'تعديل التصنيف' : 'تصنيف جديد'}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={save.isPending}>
            إلغاء
          </Button>
          <Button type="submit" form="category-form" loading={save.isPending}>
            حفظ
          </Button>
        </>
      }
    >
      <form id="category-form" onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
        {formError && <Alert>{formError}</Alert>}
        <Input label="اسم التصنيف" required error={errors.name?.message} {...register('name')} />
        <Select label="يُستخدم" error={errors.type?.message} {...register('type')}>
          {Object.entries(typeLabels).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </Select>
      </form>
    </Modal>
  );
}

// ==================== الوسوم ====================

function TagsCard() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [deleting, setDeleting] = useState<AdminTag | null>(null);

  const params = compact({ page, search }) as { page: number; search?: string };

  const query = useQuery({
    queryKey: [...adminKeys.tags, params],
    queryFn: () => adminApi.tags(params),
    placeholderData: keepPreviousData,
  });

  const remove = useActionMutation({
    mutationFn: adminApi.deleteTag,
    invalidate: [adminKeys.tags],
    onSuccess: () => setDeleting(null),
  });

  const columns: Column<AdminTag>[] = [
    { key: 'name', header: 'الوسم', cell: (tag) => <span className="font-medium text-ink">#{tag.name}</span> },
    { key: 'posts', header: 'المنشورات', align: 'center', cell: (tag) => formatNumber(tag.posts_count) },
    {
      key: 'actions',
      header: '',
      align: 'end',
      cell: (tag) => (
        <Button variant="ghost" size="icon" aria-label="حذف الوسم" onClick={() => setDeleting(tag)}>
          <Trash2 className="size-4 text-danger-600" />
        </Button>
      ),
    },
  ];

  return (
    <Card className="h-fit">
      <CardHeader title="الوسوم" />
      <div className="border-b border-line p-4">
        <SearchInput
          className="sm:w-full"
          value={search}
          placeholder="بحث عن وسم…"
          onChange={(value) => {
            setSearch(value);
            setPage(1);
          }}
        />
      </div>
      <div className="[&_table]:min-w-0">
        <DataTable
          columns={columns}
          rows={query.data?.data}
          rowKey={(tag) => tag.id}
          isLoading={query.isLoading}
          isFetching={query.isFetching}
          error={query.error}
          onRetry={() => query.refetch()}
          meta={query.data?.meta}
          onPageChange={setPage}
          emptyTitle="لا توجد وسوم"
        />
      </div>

      <ConfirmDialog
        open={deleting !== null}
        onClose={() => setDeleting(null)}
        title="حذف الوسم"
        message={
          <>
            سيُزال الوسم <strong>#{deleting?.name}</strong> من كل المنشورات التي تستخدمه.
          </>
        }
        confirmLabel="حذف"
        loading={remove.isPending}
        onConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </Card>
  );
}
