const dateFormatter = new Intl.DateTimeFormat('ar', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  numberingSystem: 'latn',
});

const dateTimeFormatter = new Intl.DateTimeFormat('ar', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  numberingSystem: 'latn',
});

const longDateFormatter = new Intl.DateTimeFormat('ar', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  numberingSystem: 'latn',
});

const numberFormatter = new Intl.NumberFormat('en-US');

const compactFormatter = new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 });

const relativeFormatter = new Intl.RelativeTimeFormat('ar', { numeric: 'auto' });

export function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : dateTimeFormatter.format(date);
}

/** "الاثنين، 5 أكتوبر 2026" */
export function formatLongDate(value: Date): string {
  return longDateFormatter.format(value);
}

/** 1200 ⟵ 1.2K */
export function formatCompact(value: number | null | undefined): string {
  return compactFormatter.format(value ?? 0);
}

export function formatNumber(value: number | null | undefined): string {
  return numberFormatter.format(value ?? 0);
}

export function formatMoney(value: number | null | undefined, currency = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(value ?? 0);
}

/** "منذ 5 دقائق" / "أمس" ... */
export function formatRelative(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';

  const seconds = Math.round((date.getTime() - Date.now()) / 1000);
  const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
  ];

  for (const [unit, size] of units) {
    if (Math.abs(seconds) >= size) {
      return relativeFormatter.format(Math.round(seconds / size), unit);
    }
  }

  return 'الآن';
}
