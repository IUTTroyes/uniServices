export type ToneColor = 'blue' | 'purple' | 'emerald' | 'amber' | 'teal' | 'indigo' | 'slate' | 'red';

export interface ToneClasses {
  container: string;
  icon: string;
}

export const TONE_CLASSES: Record<ToneColor, ToneClasses> = {
  blue: {
    container: 'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-900/60',
    icon: 'text-blue-600 dark:text-blue-400'
  },
  teal: {
    container: 'bg-teal-50 dark:bg-teal-950/40 border-teal-200 dark:border-teal-900/60',
    icon: 'text-teal-600 dark:text-teal-400'
  },
  purple: {
    container: 'bg-purple-50 dark:bg-purple-950/40 border-purple-200 dark:border-purple-900/60',
    icon: 'text-purple-600 dark:text-purple-400'
  },
  emerald: {
    container: 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900/60',
    icon: 'text-emerald-600 dark:text-emerald-400'
  },
  amber: {
    container: 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900/60',
    icon: 'text-amber-600 dark:text-amber-400'
  },
  indigo: {
    container: 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-200 dark:border-indigo-900/60',
    icon: 'text-indigo-600 dark:text-indigo-400'
  },
  slate: {
    container: 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
    icon: 'text-slate-600 dark:text-slate-400'
  },
  red: {
    container: 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900/60',
    icon: 'text-red-600 dark:text-red-400'
  }
};
