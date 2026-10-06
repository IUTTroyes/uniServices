export type FormTone = 'primary' | 'blue' | 'emerald' | 'purple' | 'amber' | 'rose' | 'slate';

/** Classes complètes (littérales) pour que Tailwind les détecte. */
export const toneClasses: Record<FormTone, string> = {
  primary: 'bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 border-primary-200 dark:border-primary-800/60',
  blue: 'bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-900/60',
  emerald: 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900/60',
  purple: 'bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-900/60',
  amber: 'bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900/60',
  rose: 'bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900/60',
  slate: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
};

/** Couleur d'icône seule (cartes de choix). */
export const toneIconClasses: Record<FormTone, string> = {
  primary: 'text-primary-600 dark:text-primary-400',
  blue: 'text-blue-600 dark:text-blue-400',
  emerald: 'text-emerald-600 dark:text-emerald-400',
  purple: 'text-purple-600 dark:text-purple-400',
  amber: 'text-amber-600 dark:text-amber-400',
  rose: 'text-rose-600 dark:text-rose-400',
  slate: 'text-slate-500 dark:text-slate-400'
};

/** Fond/bordure d'une carte de choix active. */
export const toneActiveClasses: Record<FormTone, string> = {
  primary: 'border-primary-300 dark:border-primary-700/80 bg-primary-50/60 dark:bg-primary-950/30',
  blue: 'border-blue-300 dark:border-blue-800/80 bg-blue-50/60 dark:bg-blue-950/30',
  emerald: 'border-emerald-300 dark:border-emerald-800/80 bg-emerald-50/60 dark:bg-emerald-950/30',
  purple: 'border-purple-300 dark:border-purple-800/80 bg-purple-50/60 dark:bg-purple-950/30',
  amber: 'border-amber-300 dark:border-amber-800/80 bg-amber-50/60 dark:bg-amber-950/30',
  rose: 'border-rose-300 dark:border-rose-800/80 bg-rose-50/60 dark:bg-rose-950/30',
  slate: 'border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/60'
};
