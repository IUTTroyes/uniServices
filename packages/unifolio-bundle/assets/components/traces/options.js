export const TYPES_ACTIVITE = [
  { value: 'sae', label: 'SAÉ' },
  { value: 'ressource', label: 'Ressource' },
  { value: 'stage', label: 'Stage' },
  { value: 'alternance', label: 'Alternance' },
  { value: 'projet_personnel', label: 'Projet personnel' },
  { value: 'associatif', label: 'Engagement associatif' },
  { value: 'autre', label: 'Autre' },
];

/** Valeurs identiques à ConceptionIaEnum / RealisationIaEnum (back). */
export const CONCEPTION_IA = [
  { value: 0, label: 'Aucune utilisation' },
  { value: 1, label: 'Utilisation modérée' },
  { value: 2, label: 'Utilisation avancée' },
];
export const CONCEPTION_IA_NON_RENSEIGNE = 3;

export const REALISATION_IA = [
  { value: 0, label: 'Aucune utilisation' },
  { value: 1, label: 'Utilisation ponctuelle' },
  { value: 2, label: 'Utilisation modérée' },
  { value: 3, label: 'Utilisation avancée' },
];
export const REALISATION_IA_NON_RENSEIGNE = 4;

export const STATUTS = [
  { value: 'brouillon', label: 'Brouillon' },
  { value: 'deposee', label: 'Déposée' },
  { value: 'en_evaluation', label: 'En évaluation' },
  { value: 'evaluee', label: 'Évaluée' },
  { value: 'a_reviser', label: 'À réviser' },
];

export const TYPES_MEDIA = [
  { value: 'image', label: 'Image', icon: 'pi pi-image', fichier: true, accept: 'image/*' },
  { value: 'pdf', label: 'PDF', icon: 'pi pi-file-pdf', fichier: true, accept: 'application/pdf' },
  { value: 'video', label: 'Vidéo', icon: 'pi pi-video', fichier: true, accept: 'video/mp4,video/webm,video/quicktime' },
  { value: 'fichier', label: 'Fichier', icon: 'pi pi-file', fichier: true, accept: '.zip,.pdf,.txt,.csv,.docx,.xlsx,.pptx,.odt,.ods,.odp' },
  { value: 'lien', label: 'Lien', icon: 'pi pi-link', url: true },
  { value: 'embed', label: 'Contenu intégré', icon: 'pi pi-external-link', url: true },
  { value: 'texte', label: 'Texte', icon: 'pi pi-align-left', texte: true },
  { value: 'code', label: 'Code', icon: 'pi pi-code', texte: true },
];

export const typeMedia = (value) => TYPES_MEDIA.find((t) => t.value === value);

export const formatTaille = (octets) => {
  if (!octets && octets !== 0) return '';
  if (octets < 1024) return `${octets} o`;
  if (octets < 1024 * 1024) return `${Math.round(octets / 1024)} Ko`;
  return `${(octets / (1024 * 1024)).toFixed(1)} Mo`;
};

/** Transforme une URL de partage (YouTube, Vimeo) en URL intégrable. */
export const urlIntegrable = (url) => {
  try {
    const u = new URL(url);
    const hote = u.hostname.replace(/^www\./, '');
    if (hote === 'youtu.be') return `https://www.youtube-nocookie.com/embed/${u.pathname.slice(1)}`;
    if (hote.endsWith('youtube.com') && u.searchParams.get('v')) return `https://www.youtube-nocookie.com/embed/${u.searchParams.get('v')}`;
    if (hote === 'vimeo.com' && /^\/\d+/.test(u.pathname)) return `https://player.vimeo.com/video${u.pathname}`;
    return url;
  } catch {
    return null;
  }
};

/** Miroir de EmbedPolicy::HOTES (back), pour proposer « contenu intégré » au lieu de « lien ». */
export const EMBED_HOTES = [
  'youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'figma.com', 'codepen.io',
  'canva.com', 'sketchfab.com', 'soundcloud.com', 'genial.ly', 'view.genial.ly', 'docs.google.com',
];

export const estIntegrable = (url) => {
  try {
    const u = new URL(url);
    if (u.protocol !== 'https:') return false;
    const hote = u.hostname.toLowerCase();
    return EMBED_HOTES.some((h) => hote === h || hote.endsWith(`.${h}`));
  } catch {
    return false;
  }
};

export const estUrl = (texte) => {
  try {
    const u = new URL(texte.trim());
    return ['http:', 'https:'].includes(u.protocol);
  } catch {
    return false;
  }
};

/** Titre proposé à partir d'un nom de fichier : « rapport_sae-2.01.pdf » → « Rapport sae 2.01 ». */
export const titreDepuisNomFichier = (nom) => {
  const base = (nom || '').replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
  return base ? base.charAt(0).toUpperCase() + base.slice(1) : '';
};
