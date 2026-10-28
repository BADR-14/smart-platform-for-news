import { Film, ImagePlus, X } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import type { PostMedia } from '@/features/shared/types';

const MAX_FILES = 5;
const MAX_SIZE_MB = 10;
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];

interface MediaPickerProps {
  /** الوسائط المحفوظة مسبقاً (عند التعديل) بعد استثناء المحذوف منها. */
  existing: PostMedia[];
  onRemoveExisting: (id: number) => void;
  files: File[];
  onFilesChange: (files: File[]) => void;
  error?: string;
  onError: (message: string | null) => void;
}

/** اختيار صور/فيديو المنشور مع معاينة وإمكانية الحذف قبل الحفظ. */
export function MediaPicker({ existing, onRemoveExisting, files, onFilesChange, error, onError }: MediaPickerProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const total = existing.length + files.length;

  const previews = useMemo(() => files.map((file) => ({ file, url: URL.createObjectURL(file) })), [files]);

  // تحرير روابط المعاينة من الذاكرة عند تغيّر الملفات أو إغلاق الصفحة
  useEffect(() => () => previews.forEach((preview) => URL.revokeObjectURL(preview.url)), [previews]);

  const addFiles = (list: FileList | null) => {
    if (!list) return;

    const incoming = Array.from(list);
    const invalid = incoming.find((file) => !ACCEPTED.includes(file.type) || file.size > MAX_SIZE_MB * 1024 * 1024);

    if (invalid) {
      onError(`الملف "${invalid.name}" غير مقبول. المسموح: صور JPG/PNG/WebP أو فيديو MP4 حتى ${MAX_SIZE_MB} ميغابايت.`);
      return;
    }

    if (total + incoming.length > MAX_FILES) {
      onError(`الحد الأقصى ${MAX_FILES} ملفات للمنشور.`);
      return;
    }

    onError(null);
    onFilesChange([...files, ...incoming]);
  };

  const tile = 'relative aspect-square overflow-hidden rounded-xl border border-line bg-brand-50';
  const removeButton = 'absolute end-1.5 top-1.5 rounded-full bg-brand-950/70 p-1 text-white hover:bg-danger-600';

  return (
    <div className="flex flex-col gap-1.5">
      <p className="text-sm font-medium text-ink-soft">الصور والفيديو</p>

      <div className="grid grid-cols-3 gap-3 sm:grid-cols-5">
        {existing.map((media) => (
          <div key={media.id} className={tile}>
            {media.type === 'video' ? <VideoTile /> : <img src={media.url} alt="" className="size-full object-cover" />}
            <button type="button" onClick={() => onRemoveExisting(media.id)} aria-label="حذف الملف" className={removeButton}>
              <X className="size-3.5" />
            </button>
          </div>
        ))}

        {previews.map(({ file, url }) => (
          <div key={url} className={tile}>
            {file.type.startsWith('video') ? <VideoTile name={file.name} /> : <img src={url} alt={file.name} className="size-full object-cover" />}
            <button
              type="button"
              onClick={() => onFilesChange(files.filter((item) => item !== file))}
              aria-label={`حذف ${file.name}`}
              className={removeButton}
            >
              <X className="size-3.5" />
            </button>
          </div>
        ))}

        {total < MAX_FILES && (
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            className="flex aspect-square flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-line-strong text-muted transition-colors hover:border-accent-500 hover:text-accent-700"
          >
            <ImagePlus className="size-6" />
            <span className="text-xs font-medium">إضافة</span>
          </button>
        )}
      </div>

      <input
        ref={inputRef}
        type="file"
        multiple
        hidden
        accept={ACCEPTED.join(',')}
        onChange={(event) => {
          addFiles(event.target.files);
          event.target.value = '';
        }}
      />

      {error ? (
        <p role="alert" className="text-xs text-danger-600">
          {error}
        </p>
      ) : (
        <p className="text-xs text-muted">
          حتى {MAX_FILES} ملفات، كل ملف حتى {MAX_SIZE_MB} ميغابايت. أول صورة تُستخدم كصورة غلاف.
        </p>
      )}
    </div>
  );
}

function VideoTile({ name }: { name?: string }) {
  return (
    <div className="flex size-full flex-col items-center justify-center gap-1 text-brand-600">
      <Film className="size-7" />
      <span className="max-w-full truncate px-2 text-[11px]">{name ?? 'فيديو'}</span>
    </div>
  );
}
