/** Outils communs aux services de requêtes du package Questionnaires. */

/** Propose un fichier reçu (blob) au téléchargement. */
export function telecharger(blob, nomFichier) {
    const url = window.URL.createObjectURL(blob);
    const lien = document.createElement('a');
    lien.href = url;
    lien.setAttribute('download', nomFichier);
    document.body.appendChild(lien);
    lien.click();
    lien.remove();
    window.URL.revokeObjectURL(url);
}
