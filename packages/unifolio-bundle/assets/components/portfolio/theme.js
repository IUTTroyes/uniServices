/** Réglages d'apparence d'un portfolio publié (miroir de ThemeNormalizer côté back et du service de rendu). */
export const POLICES = {
  systeme: { libelle: 'Moderne', pile: "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" },
  classique: { libelle: 'Classique', pile: "Georgia, 'Iowan Old Style', 'Times New Roman', serif" },
  humaniste: { libelle: 'Humaniste', pile: "'Gill Sans', 'Trebuchet MS', Calibri, 'Segoe UI', sans-serif" },
  arrondie: { libelle: 'Arrondie', pile: "ui-rounded, 'Hiragino Maru Gothic ProN', Quicksand, 'Varela Round', 'Segoe UI', sans-serif" },
  technique: { libelle: 'Technique', pile: "ui-monospace, 'SF Mono', Menlo, Consolas, monospace" },
};

export const ENTETES = [{ v: 'clair', l: 'Teinté' }, { v: 'couleur', l: 'Couleur pleine' }, { v: 'sobre', l: 'Sobre' }];
export const LARGEURS = [{ v: 'etroite', l: 'Étroite' }, { v: 'standard', l: 'Standard' }, { v: 'large', l: 'Large' }];
export const COINS = [{ v: 'arrondis', l: 'Arrondis' }, { v: 'droits', l: 'Droits' }];

export const PALETTES = [
  { nom: 'Émeraude', couleur: '#047857' },
  { nom: 'Bleu', couleur: '#1d4ed8' },
  { nom: 'Violet', couleur: '#7c3aed' },
  { nom: 'Framboise', couleur: '#be123c' },
  { nom: 'Orange', couleur: '#c2410c' },
  { nom: 'Turquoise', couleur: '#0f766e' },
  { nom: 'Ardoise', couleur: '#334155' },
  { nom: 'Fuchsia', couleur: '#a21caf' },
];

export const THEME_DEFAUT = { couleur: '#047857', police: 'systeme', entete: 'clair', largeur: 'standard', coins: 'arrondis' };

const luminance = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));

  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

/** Rapport de contraste WCAG entre deux couleurs #rrggbb. */
export const contraste = (a, b = '#ffffff') => {
  const [la, lb] = [luminance(a), luminance(b)];

  return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
};

export const COULEUR_VALIDE = /^#[0-9a-fA-F]{6}$/;
export const CONTRASTE_MINI = 4.5;
export const couleurLisible = (hex) => COULEUR_VALIDE.test(hex) && contraste(hex) >= CONTRASTE_MINI;
export const texteSur = (hex) => (contraste(hex, '#ffffff') >= contraste(hex, '#111111') ? '#ffffff' : '#111111');
