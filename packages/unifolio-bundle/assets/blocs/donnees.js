import { urlFichier } from '../requests/traceService';

/** Média d'une trace de l'étudiant, retrouvé dans les données de rendu. */
export const trouverMediaTrace = (donnees, traceUuid, mediaId) => {
  const trace = donnees?.traces?.find((t) => t.uuid === traceUuid);
  const media = trace?.medias?.find((m) => m.id === mediaId);

  return media ? { trace, media } : null;
};

/**
 * Résout la source d'un bloc média : { trace, media } (média d'une trace) ou { url } (image externe).
 * @returns {{ src: string|null, nom: string|null, type: string|null, telechargement: string|null }}
 */
export const resoudreSource = (donnees, source) => {
  if (source?.url) return { src: source.url, nom: null, type: 'externe', telechargement: source.url };
  if (source?.trace) {
    const trouve = trouverMediaTrace(donnees, source.trace, source.media);
    if (trouve?.media.fichier) {
      return {
        src: urlFichier(trouve.media.fichier.url),
        nom: trouve.media.fichier.nom,
        type: trouve.media.type,
        telechargement: urlFichier(trouve.media.fichier.url, true),
      };
    }
  }

  return { src: null, nom: null, type: null, telechargement: null };
};

/** Médias de l'étudiant proposés par le sélecteur, filtrés par nature (image, fichier). */
export const mediasProposes = (donnees, filtre) => {
  const accepte = (m) => (filtre === 'image' ? m.type === 'image' : filtre === 'fichier' ? ['pdf', 'fichier', 'video', 'image'].includes(m.type) : !!m.fichier);

  return (donnees?.traces ?? []).flatMap((t) => (t.medias ?? []).filter((m) => m.fichier && accepte(m)).map((m) => ({
    trace: t.uuid,
    media: m.id,
    traceTitre: t.titre,
    type: m.type,
    nom: m.fichier.nom,
    src: urlFichier(m.fichier.url),
  })));
};

/** Traces à afficher pour un bloc « liste de traces ». */
export const tracesFiltrees = (donnees, { competence = null, maximum = 6 } = {}) =>
  (donnees?.traces ?? [])
    .filter((t) => !competence || t.competences.some((c) => c.id === Number(competence)))
    .slice(0, maximum);

export const pourcentage = (partie, total) => (total > 0 ? Math.round((partie * 100) / total) : 0);
