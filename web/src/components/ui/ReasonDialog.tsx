import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from './Button';
import { Textarea } from './FormField';
import { Modal } from './Modal';

interface ReasonDialogProps {
  open: boolean;
  onClose: () => void;
  onSubmit: (reason: string) => void;
  title: string;
  description?: ReactNode;
  label: string;
  placeholder?: string;
  confirmLabel: string;
  required?: boolean;
  tone?: 'danger' | 'primary';
  loading?: boolean;
}

/** نافذة لإجراء يحتاج سبباً مكتوباً (حظر حساب، رفض منشور ...). */
export function ReasonDialog(props: ReasonDialogProps) {
  // المحتوى يُركَّب عند الفتح فقط، فيبدأ الحقل فارغاً في كل مرة
  return props.open ? <ReasonDialogContent {...props} /> : null;
}

function ReasonDialogContent({
  onClose,
  onSubmit,
  title,
  description,
  label,
  placeholder,
  confirmLabel,
  required = true,
  tone = 'danger',
  loading = false,
}: ReasonDialogProps) {
  const [reason, setReason] = useState('');
  const trimmed = reason.trim();

  return (
    <Modal
      open
      onClose={onClose}
      title={title}
      size="sm"
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={loading}>
            إلغاء
          </Button>
          <Button
            variant={tone === 'danger' ? 'danger' : 'primary'}
            loading={loading}
            disabled={required && trimmed.length === 0}
            onClick={() => onSubmit(trimmed)}
          >
            {confirmLabel}
          </Button>
        </>
      }
    >
      <div className="flex flex-col gap-4">
        {description && <div className="text-sm leading-relaxed text-ink-soft">{description}</div>}
        <Textarea
          label={label}
          required={required}
          rows={3}
          maxLength={255}
          placeholder={placeholder}
          value={reason}
          onChange={(event) => setReason(event.target.value)}
          autoFocus
        />
      </div>
    </Modal>
  );
}
