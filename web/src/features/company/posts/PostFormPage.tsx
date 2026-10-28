import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowRight, Save, Send } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { z } from 'zod';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input, Select, Textarea } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { ErrorState, LoadingState } from '@/components/ui/States';
import { useCategories } from '@/features/shared/categories';
import { postStatusMeta, postTypeLabels } from '@/features/shared/labels';
import type { Post } from '@/features/shared/types';
import { cn } from '@/lib/utils/cn';
import { applyServerErrors } from '@/lib/utils/form';
import { companyApi } from '../api';
import type { PostInput } from '../api';
import { companyKeys } from '../keys';
import { LocationPicker } from './components/LocationPicker';
import { MediaPicker } from './components/MediaPicker';
import { TagInput } from './components/TagInput';

const locationSchema = z.object({ location: z.string(), latitude: z.number(), longitude: z.number() });

const schema = z
  .object({
    title: z.string().trim().min(1, 'العنوان مطلوب').max(255, 'العنوان طويل جداً'),
    description: z.string().trim().max(10_000, 'الوصف طويل جداً'),
    post_type: z.enum(['lecture', 'exhibition', 'event', 'course']),
    category_id: z.string().min(1, 'اختر التصنيف'),
    is_online: z.boolean(),
    place: locationSchema.nullable(),
    start_date: z.string(),
    end_date: z.string(),
    tags: z.array(z.string()).max(5),
  })
  .superRefine((values, context) => {
    if (!values.is_online && !values.place) {
      context.addIssue({ code: 'custom', path: ['place'], message: 'حدّد موقع الفعالية على الخريطة' });
    }

    if (values.post_type === 'event') {
      if (!values.start_date) context.addIssue({ code: 'custom', path: ['start_date'], message: 'تاريخ البداية مطلوب للفعاليات' });
      if (!values.end_date) context.addIssue({ code: 'custom', path: ['end_date'], message: 'تاريخ النهاية مطلوب للفعاليات' });
    }

    if (values.start_date && values.end_date && values.end_date < values.start_date) {
      context.addIssue({ code: 'custom', path: ['end_date'], message: 'تاريخ النهاية يجب أن يكون بعد البداية' });
    }
  });

type FormValues = z.infer<typeof schema>;

/** صفحة إنشاء منشور جديد أو تعديل منشور موجود (حسب وجود postId في المسار). */
export function PostFormPage() {
  const { postId } = useParams();
  const id = postId ? Number(postId) : null;

  const postQuery = useQuery({
    queryKey: companyKeys.post(id ?? 0),
    queryFn: () => companyApi.post(id!),
    enabled: id !== null,
  });

  if (id !== null && postQuery.isLoading) return <LoadingState />;
  if (id !== null && (postQuery.error || !postQuery.data)) {
    return <ErrorState error={postQuery.error} onRetry={() => postQuery.refetch()} />;
  }

  // key يضمن إعادة تهيئة النموذج عند الانتقال بين منشورين
  return <PostForm key={id ?? 'new'} post={postQuery.data ?? null} />;
}

function PostForm({ post }: { post: Post | null }) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const categories = useCategories('post');

  const [files, setFiles] = useState<File[]>([]);
  const [removedMediaIds, setRemovedMediaIds] = useState<number[]>([]);
  const [mediaError, setMediaError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      title: post?.title ?? '',
      description: post?.description ?? '',
      post_type: post?.post_type ?? 'event',
      category_id: post?.category ? String(post.category.id) : '',
      is_online: post?.is_online ?? true,
      place:
        post && !post.is_online && post.latitude !== null && post.longitude !== null
          ? { location: post.location ?? '', latitude: post.latitude, longitude: post.longitude }
          : null,
      start_date: post?.start_date ?? '',
      end_date: post?.end_date ?? '',
      tags: post?.tags.map((tag) => tag.name) ?? [],
    },
  });

  const [isOnline, postType] = useWatch({ control, name: ['is_online', 'post_type'] });

  const save = useMutation({
    mutationFn: (input: PostInput) => (post ? companyApi.updatePost(post.id, input) : companyApi.createPost(input)),
    onSuccess: async (response) => {
      toast.success(response.message ?? 'تم حفظ المنشور');
      await queryClient.invalidateQueries({ queryKey: companyKeys.posts });
      await queryClient.invalidateQueries({ queryKey: companyKeys.overview });
      navigate('/company/posts');
    },
  });

  const submit = (action?: PostInput['action']) =>
    handleSubmit(async (values) => {
      setFormError(null);

      try {
        await save.mutateAsync({
          title: values.title,
          description: values.description,
          post_type: values.post_type,
          category_id: Number(values.category_id),
          is_online: values.is_online,
          location: values.place?.location,
          latitude: values.place?.latitude,
          longitude: values.place?.longitude,
          start_date: values.start_date,
          end_date: values.end_date,
          tags: values.tags,
          media: files,
          remove_media_ids: removedMediaIds,
          action,
        });
      } catch (error) {
        const message = applyServerErrors(error, setError, ['title', 'description', 'post_type', 'category_id', 'start_date', 'end_date', 'tags']);
        setFormError(message ?? 'تحقق من الحقول المشار إليها.');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    })();

  const existingMedia = post?.media.filter((media) => !removedMediaIds.includes(media.id)) ?? [];
  const isModerated = post?.status === 'under_review' || post?.status === 'rejected';

  return (
    <>
      <PageHeader
        title={post ? 'تعديل المنشور' : 'منشور جديد'}
        description={post ? `الحالة الحالية: ${postStatusMeta[post.status].label}` : 'يُنشر المنشور فوراً ويظهر لكل مستخدمي المنصة.'}
        actions={
          <Link to="/company/posts">
            <Button variant="ghost" icon={<ArrowRight className="size-4" />}>
              رجوع
            </Button>
          </Link>
        }
      />

      <form noValidate onSubmit={(event) => event.preventDefault()} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div className="space-y-6">
          {formError && <Alert>{formError}</Alert>}

          {post?.moderation_note && (
            <Alert tone="warning" title="ملاحظة الإدارة">
              {post.moderation_note}
            </Alert>
          )}

          <Card>
            <CardHeader title="المحتوى" />
            <CardBody className="flex flex-col gap-4">
              <Input label="العنوان" required maxLength={255} error={errors.title?.message} {...register('title')} />
              <Textarea
                label="الوصف"
                rows={7}
                placeholder="عرّف بالفعالية: المحاور، المحاضرون، الفئة المستهدفة، طريقة التسجيل…"
                error={errors.description?.message}
                {...register('description')}
              />
              <Controller
                control={control}
                name="tags"
                render={({ field }) => <TagInput value={field.value} onChange={field.onChange} error={errors.tags?.message} />}
              />
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="الوسائط" />
            <CardBody>
              <MediaPicker
                existing={existingMedia}
                onRemoveExisting={(mediaId) => setRemovedMediaIds((current) => [...current, mediaId])}
                files={files}
                onFilesChange={setFiles}
                error={mediaError ?? undefined}
                onError={setMediaError}
              />
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="مكان الانعقاد" />
            <CardBody className="flex flex-col gap-4">
              <Controller
                control={control}
                name="is_online"
                render={({ field }) => (
                  <div role="radiogroup" aria-label="نوع الحضور" className="grid grid-cols-2 gap-3">
                    {[
                      { value: true, label: 'أونلاين', hint: 'عن بُعد عبر الإنترنت' },
                      { value: false, label: 'حضوري', hint: 'في موقع محدد' },
                    ].map((option) => (
                      <button
                        key={option.label}
                        type="button"
                        role="radio"
                        aria-checked={field.value === option.value}
                        onClick={() => field.onChange(option.value)}
                        className={cn(
                          'rounded-xl border p-3.5 text-start transition-colors',
                          field.value === option.value
                            ? 'border-accent-500 bg-accent-50 ring-1 ring-accent-500'
                            : 'border-line-strong hover:border-brand-300',
                        )}
                      >
                        <span className="block font-semibold text-ink">{option.label}</span>
                        <span className="block text-xs text-muted">{option.hint}</span>
                      </button>
                    ))}
                  </div>
                )}
              />

              {!isOnline && (
                <Controller
                  control={control}
                  name="place"
                  render={({ field }) => <LocationPicker value={field.value} onChange={field.onChange} error={errors.place?.message} />}
                />
              )}
            </CardBody>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader title="التفاصيل" />
            <CardBody className="flex flex-col gap-4">
              <Select label="نوع المنشور" required error={errors.post_type?.message} {...register('post_type')}>
                {Object.entries(postTypeLabels).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </Select>

              <Select label="التصنيف" required error={errors.category_id?.message} {...register('category_id')}>
                <option value="">اختر التصنيف</option>
                {categories.data?.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </Select>

              <Input
                label="تاريخ البداية"
                type="date"
                required={postType === 'event'}
                error={errors.start_date?.message}
                {...register('start_date')}
              />
              <Input
                label="تاريخ النهاية"
                type="date"
                required={postType === 'event'}
                error={errors.end_date?.message}
                {...register('end_date')}
              />
            </CardBody>
          </Card>

          <Card>
            <CardBody className="flex flex-col gap-3">
              {isModerated ? (
                <>
                  <Alert tone="info">
                    المنشور {postStatusMeta[post.status].label}. يمكنك تعديل محتواه وحفظه، وإعادة نشره قرار الإدارة بعد المراجعة.
                  </Alert>
                  <Button icon={<Save className="size-4" />} loading={save.isPending} onClick={() => submit()}>
                    حفظ التعديلات
                  </Button>
                </>
              ) : (
                <>
                  <Button variant="accent" size="lg" icon={<Send className="size-5" />} loading={save.isPending} onClick={() => submit('publish')}>
                    {post?.status === 'published' ? 'حفظ التعديلات' : 'نشر'}
                  </Button>
                  <Button variant="outline" icon={<Save className="size-4" />} disabled={save.isPending} onClick={() => submit('draft')}>
                    حفظ كمسودة
                  </Button>
                </>
              )}
            </CardBody>
          </Card>
        </div>
      </form>
    </>
  );
}
