// Remplace requests/traceService.js dans le rendu statique : l'instantané contient déjà des adresses relatives
// à la publication (media/…), il n'y a ni API ni authentification à appeler.
export const urlFichier = (chemin) => chemin || null;
