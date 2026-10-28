import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** يدمج أصناف Tailwind ويحل التعارض بينها (الأخير يغلب). */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}
