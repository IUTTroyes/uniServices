import tools from '../global-data/tools.generated.json';
import colors from '../global-data/application-colors.json';

const palette = new Set(colors);
const aliases = new Map(tools.flatMap(tool => (tool.aliases ?? []).map(alias => [alias, tool.urlSlug])));

function buildColorClasses(color) {
  if (!palette.has(color)) {
    return {
      badgeClasses: 'bg-surface-100 text-surface-700 border-surface-200 dark:bg-surface-800 dark:text-surface-300 dark:border-surface-700',
      iconBgClasses: 'text-surface-600 bg-surface-50 dark:bg-surface-900 border-surface-200 dark:border-surface-800',
      dotClass: 'bg-surface-400',
    };
  }

  return {
    badgeClasses: `bg-${color}-100! text-${color}-800! border-${color}-200! dark:bg-${color}-950/60! dark:text-${color}-300! dark:border-${color}-800!`,
    iconBgClasses: `text-${color}-600! bg-${color}-50! dark:bg-${color}-950/40! border-${color}-200! dark:border-${color}-800!`,
    dotClass: `bg-${color}-500`,
  };
}

export const APPLICATION_REGISTRY = Object.fromEntries(tools.map(tool => {
  const primaryColor = palette.has(tool.primaryColor) ? tool.primaryColor : 'surface';
  return [tool.urlSlug, {
    slug: tool.urlSlug,
    name: tool.name,
    shortLabel: tool.shortLabel ?? tool.name,
    brand: tool.brand ?? null,
    primaryColor,
    color: primaryColor,
    icon: tool.icon ?? 'pi pi-box',
    isDefault: tool.isDefault ?? false,
    ...buildColorClasses(primaryColor),
  }];
}));

export function normalizeAppSlug(appInput) {
  if (!appInput) return '';
  const raw = typeof appInput === 'object'
    ? (appInput.urlSlug || appInput.slug || appInput.name || '')
    : String(appInput);
  const clean = raw.trim().toLowerCase();

  return Object.hasOwn(APPLICATION_REGISTRY, clean) ? clean : (aliases.get(clean) ?? clean);
}

export function getApplicationMeta(appInput) {
  const slug = normalizeAppSlug(appInput);
  if (Object.hasOwn(APPLICATION_REGISTRY, slug)) {
    return APPLICATION_REGISTRY[slug];
  }

  const rawLabel = typeof appInput === 'object' && appInput
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
    ...buildColorClasses('surface'),
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
