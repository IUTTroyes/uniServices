import { format, formatRelative } from 'date-fns';
import { fr } from 'date-fns/locale';

export function formatDate(date: Date | string | number): string {
  const d = date instanceof Date ? date : new Date(date);
  return format(d, 'dd/MM/yyyy', { locale: fr });
}

export function formatRelativeTime(date: Date | string | number): string {
  const d = date instanceof Date ? date : new Date(date);
  return formatRelative(d, new Date(), { locale: fr });
}

export function formatDuration(milliseconds: number): string {
  const minutes = Math.round(milliseconds / (1000 * 60));
  if (minutes < 60) return `${minutes}min`;
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  return `${hours}h${remainingMinutes > 0 ? ` ${remainingMinutes}min` : ''}`;
}
