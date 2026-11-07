/**
 * Markdown simplifié pour les textes d'un portfolio : **gras**, *italique*, `code`, [lien](https://…),
 * listes (- ou 1.), paragraphes. Tout le HTML saisi est échappé AVANT mise en forme : le résultat ne
 * peut contenir que les balises ci-dessous. Ne dépend d'aucune API navigateur (rendu serveur possible).
 */
const echapper = (s) => String(s ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

const inline = (texte) => {
  let html = echapper(texte);
  const codes = [];
  html = html.replace(/`([^`\n]+)`/g, (_, c) => `\u0000${codes.push(c) - 1}\u0000`);
  html = html.replace(/\[([^\]\n]{1,200})\]\((https?:\/\/[^\s)]{1,2000})\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer nofollow">$1</a>');
  html = html.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
  html = html.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');

  return html.replace(/\u0000(\d+)\u0000/g, (_, i) => `<code>${codes[Number(i)]}</code>`);
};

export const rendreMarkdown = (texte) => {
  const lignes = String(texte ?? '').replace(/\r\n/g, '\n').split('\n');
  const sortie = [];
  let paragraphe = [];
  let liste = null;

  const fermerParagraphe = () => {
    if (paragraphe.length) sortie.push(`<p>${paragraphe.map(inline).join('<br>')}</p>`);
    paragraphe = [];
  };
  const fermerListe = () => {
    if (liste) sortie.push(`<${liste.balise}>${liste.items.map((i) => `<li>${inline(i)}</li>`).join('')}</${liste.balise}>`);
    liste = null;
  };

  for (const ligne of lignes) {
    const puce = /^\s*[-*]\s+(.+)$/.exec(ligne);
    const numero = /^\s*\d+[.)]\s+(.+)$/.exec(ligne);
    if (puce || numero) {
      fermerParagraphe();
      const balise = puce ? 'ul' : 'ol';
      if (liste && liste.balise !== balise) fermerListe();
      liste ??= { balise, items: [] };
      liste.items.push((puce ?? numero)[1]);
    } else if (ligne.trim() === '') {
      fermerParagraphe();
      fermerListe();
    } else {
      fermerListe();
      paragraphe.push(ligne.trim());
    }
  }
  fermerParagraphe();
  fermerListe();

  return sortie.join('');
};

/** Texte brut (sans mise en forme), pour les aperçus courts. */
export const extraitTexte = (texte, longueur = 120) => {
  const brut = String(texte ?? '').replace(/[*_`#>-]|\[([^\]]*)\]\([^)]*\)/g, '$1').replace(/\s+/g, ' ').trim();

  return brut.length > longueur ? `${brut.slice(0, longueur - 1)}…` : brut;
};
