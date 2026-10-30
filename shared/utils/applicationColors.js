/**
 * Application metadata and color definitions.
 * Every application's primary color strictly matches the `primaryColor` declared in its `manifest.ts`.
 *
 * References:
 * - intranet / intranet: 'violet' (packages/intranet-bundle/assets/manifest.ts)
 * - stage / stages:       'teal'   (packages/stage-bundle/assets/manifest.ts)
 * - questionnaire:        'green'  (packages/questionnaire-bundle/assets/manifest.ts)
 * - documents / document: 'blue'   (packages/document-bundle/assets/manifest.ts)
 * - helpdesk:             'orange' (packages/helpdesk-bundle/assets/manifest.ts)
 * - unifolio:             'emerald'(packages/unifolio-bundle/assets/manifest.ts)
 * - finance:              'emerald'(packages/finance-bundle/assets/manifest.ts)
 * - auth / portail:       'yellow' (packages/auth-bundle/assets/manifest.ts)
 * - correcto:             'rose'   (shared/global-data/external-tools/correcto.meta.json)
 */

export const APPLICATION_REGISTRY = {
  intranet: {
    slug: 'intranet',
    name: 'Intranet',
    shortLabel: 'Intranet',
    primaryColor: 'violet',
    color: 'violet',
    icon: 'pi pi-desktop',
    isDefault: true,
    badgeClasses: '!bg-violet-100 !text-violet-800 !border-violet-200 !dark:bg-violet-950/60 !dark:text-violet-300 !dark:border-violet-800',
    iconBgClasses: '!text-violet-600 !bg-violet-50 !dark:bg-violet-950/40 !border-violet-200 !dark:border-violet-800',
    dotClass: 'bg-violet-500',
  },
  stage: {
    slug: 'stage',
    name: 'Stage/Alternance',
    shortLabel: 'Stages',
    primaryColor: 'teal',
    color: 'teal',
    icon: 'pi pi-briefcase',
    isDefault: false,
    badgeClasses: '!bg-teal-100 !text-teal-800 !border-teal-200 !dark:bg-teal-950/60 !dark:text-teal-300 !dark:border-teal-800',
    iconBgClasses: '!text-teal-600 !bg-teal-50 !dark:bg-teal-950/40 !border-teal-200 !dark:border-teal-800',
    dotClass: 'bg-teal-500',
  },
  questionnaire: {
    slug: 'questionnaire',
    name: 'Questionnaires',
    shortLabel: 'Questionnaires',
    primaryColor: 'green',
    color: 'green',
    icon: 'pi pi-check-square',
    isDefault: false,
    badgeClasses: '!bg-green-100 !text-green-800 !border-green-200 !dark:bg-green-950/60 !dark:text-green-300 !dark:border-green-800',
    iconBgClasses: '!text-green-600 !bg-green-50 !dark:bg-green-950/40 !border-green-200 !dark:border-green-800',
    dotClass: 'bg-green-500',
  },
  documents: {
    slug: 'documents',
    name: 'Documents',
    shortLabel: 'Documents',
    primaryColor: 'blue',
    color: 'blue',
    icon: 'pi pi-folder',
    isDefault: false,
    badgeClasses: '!bg-blue-100 !text-blue-800 !border-blue-200 !dark:bg-blue-950/60 !dark:text-blue-300 !dark:border-blue-800',
    iconBgClasses: '!text-blue-600 !bg-blue-50 !dark:bg-blue-950/40 !border-blue-200 !dark:border-blue-800',
    dotClass: 'bg-blue-500',
  },
  helpdesk: {
    slug: 'helpdesk',
    name: 'HelpDesk',
    shortLabel: 'HelpDesk',
    primaryColor: 'orange',
    color: 'orange',
    icon: 'pi pi-question-circle',
    isDefault: false,
    badgeClasses: '!bg-orange-100 !text-orange-800 !border-orange-200 !dark:bg-orange-950/60 !dark:text-orange-300 !dark:border-orange-800',
    iconBgClasses: '!text-orange-600 !bg-orange-50 !dark:bg-orange-950/40 !border-orange-200 !dark:border-orange-800',
    dotClass: 'bg-orange-500',
  },
  unifolio: {
    slug: 'unifolio',
    name: 'UniFolio',
    shortLabel: 'UniFolio',
    primaryColor: 'lime',
    color: 'lime',
    icon: 'pi pi-book',
    isDefault: false,
    badgeClasses: '!bg-lime-100 !text-lime-800 !border-lime-200 !dark:bg-lime-950/60 !dark:text-lime-300 !dark:border-lime-800',
    iconBgClasses: '!text-lime-600 !bg-lime-50 !dark:bg-lime-950/40 !border-lime-200 !dark:border-lime-800',
    dotClass: 'bg-lime-500',
  },
  finance: {
    slug: 'finance',
    name: 'Finances & Achats',
    shortLabel: 'Finance',
    primaryColor: 'yellow',
    color: 'yellow',
    icon: 'pi pi-wallet',
    isDefault: false,
    badgeClasses: '!bg-yellow-100 !text-yellow-800 !border-yellow-200 !dark:bg-yellow-950/60 !dark:text-yellow-300 !dark:border-yellow-800',
    iconBgClasses: '!text-yellow-600 !bg-yellow-50 !dark:bg-yellow-950/40 !border-yellow-200 !dark:border-yellow-800',
    dotClass: 'bg-yellow-500',
  },
  auth: {
    slug: 'auth',
    name: 'Mon Espace / Portail',
    shortLabel: 'Portail',
    primaryColor: 'yellow',
    color: 'yellow',
    icon: 'pi pi-user',
    isDefault: false,
    badgeClasses: '!bg-amber-100 !text-amber-800 !border-amber-200 !dark:bg-amber-950/60 !dark:text-amber-300 !dark:border-amber-800',
    iconBgClasses: '!text-amber-600 !bg-amber-50 !dark:bg-amber-950/40 !border-amber-200 !dark:border-amber-800',
    dotClass: 'bg-amber-500',
  },
  correcto: {
    slug: 'correcto',
    name: 'Correcto',
    shortLabel: 'Correcto',
    primaryColor: 'rose',
    color: 'rose',
    icon: 'pi pi-pencil',
    isDefault: false,
    badgeClasses: '!bg-rose-100 !text-rose-800 !border-rose-200 !dark:bg-rose-950/60 !dark:text-rose-300 !dark:border-rose-800',
    iconBgClasses: '!text-rose-600 !bg-rose-50 !dark:bg-rose-950/40 !border-rose-200 !dark:border-rose-800',
    dotClass: 'bg-rose-500',
  },
  message: {
    slug: 'message',
    name: 'Messagerie',
    shortLabel: 'Messages',
    primaryColor: 'cyan',
    color: 'cyan',
    icon: 'pi pi-inbox',
    isDefault: false,
    badgeClasses: '!bg-cyan-100 !text-cyan-800 !border-cyan-200 !dark:bg-cyan-950/60 !dark:text-cyan-300 !dark:border-cyan-800',
    iconBgClasses: '!text-cyan-600 !bg-cyan-50 !dark:bg-cyan-950/40 !border-cyan-200 !dark:border-cyan-800',
    dotClass: 'bg-cyan-500',
  },
};

/**
 * Normalise any slug or app name variation to the canonical registry key.
 */
export function normalizeAppSlug(appInput) {
  if (!appInput) return '';
  const raw = typeof appInput === 'object'
    ? (appInput.urlSlug || appInput.slug || appInput.name || '')
    : String(appInput);

  const clean = raw.trim().toLowerCase();

  const aliases = {
    intranet: 'intranet',
    stages: 'stage',
    questionnaires: 'questionnaire',
    document: 'documents',
    finances: 'finance',
    portail: 'auth',
    'help-desk': 'helpdesk',
    portfolio: 'unifolio',
    portfolios: 'unifolio',
    messages: 'message',
    messagerie: 'message',
  };

  return aliases[clean] || clean;
}

/**
 * Returns the full application metadata matching manifest.ts primaryColor.
 */
export function getApplicationMeta(appInput) {
  const slug = normalizeAppSlug(appInput);
  if (slug && APPLICATION_REGISTRY[slug]) {
    return APPLICATION_REGISTRY[slug];
  }

  // Fallback for unknown / generic tools
  const rawLabel = typeof appInput === 'object'
    ? (appInput.name || appInput.urlSlug || 'Application')
    : String(appInput || 'Application');

  const formattedLabel = rawLabel.charAt(0).toUpperCase() + rawLabel.slice(1);

  return {
    slug: slug || 'unknown',
    name: formattedLabel,
    shortLabel: formattedLabel,
    primaryColor: 'surface',
    color: 'surface',
    icon: 'pi pi-box',
    isDefault: false,
    badgeClasses: 'bg-surface-100 text-surface-700 border-surface-200 dark:bg-surface-800 dark:text-surface-300 dark:border-surface-700',
    iconBgClasses: 'text-surface-600 bg-surface-50 dark:bg-surface-900 border-surface-200 dark:border-surface-800',
    dotClass: 'bg-surface-400',
  };
}

/**
 * Helper specifically for PrimeVue Tag or badges: returns { label, color, badgeClasses, icon }
 */
export function getAppTagInfo(appInput) {
  const meta = getApplicationMeta(appInput);
  return {
    label: meta.shortLabel || meta.name,
    color: meta.primaryColor,
    badgeClasses: meta.badgeClasses,
    icon: meta.icon,
    // Keep severity for backward compatibility if ever read
    severity: meta.primaryColor === 'green' || meta.primaryColor === 'emerald'
      ? 'success'
      : meta.primaryColor === 'orange' || meta.primaryColor === 'yellow'
        ? 'warn'
        : meta.primaryColor === 'rose'
          ? 'danger'
          : 'info',
  };
}
