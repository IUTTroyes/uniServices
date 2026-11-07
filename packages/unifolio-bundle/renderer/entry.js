import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import BlocRender from '../assets/blocs/BlocRender.vue';

const ECHAPPEMENTS = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
const esc = (valeur) => String(valeur ?? '').replace(/[&<>"']/g, (c) => ECHAPPEMENTS[c]);

// Apparence : valeurs de liste blanche uniquement (le back les valide déjà ; on ne fait pas confiance à l'entrée pour autant).
const POLICES = {
  systeme: "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
  classique: "Georgia, 'Iowan Old Style', 'Times New Roman', serif",
  humaniste: "'Gill Sans', 'Trebuchet MS', Calibri, 'Segoe UI', sans-serif",
  arrondie: "ui-rounded, 'Hiragino Maru Gothic ProN', Quicksand, 'Varela Round', 'Segoe UI', sans-serif",
  technique: "ui-monospace, 'SF Mono', Menlo, Consolas, monospace",
};
const LARGEURS = { etroite: '44rem', standard: '60rem', large: '76rem' };
const ENTETES = ['clair', 'couleur', 'sobre'];

const luminance = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));

  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const variables = (theme = {}) => {
  const couleur = /^#[0-9a-f]{6}$/.test(theme.couleur ?? '') ? theme.couleur : '#047857';
  const contraste = (1.05 / (luminance(couleur) + 0.05)) >= (luminance(couleur) + 0.05) / 0.05 ? '#ffffff' : '#111111';

  return [
    `--pf-accent:${couleur}`,
    `--pf-accent-contraste:${contraste}`,
    `--pf-police:${POLICES[theme.police] ?? POLICES.systeme}`,
    `--pf-largeur:${LARGEURS[theme.largeur] ?? LARGEURS.standard}`,
    `--pf-rayon:${theme.coins === 'droits' ? '0' : '0.75rem'}`,
  ].join(';');
};

const rendreBlocs = async (blocs, donnees) => {
  const app = createSSRApp({
    render: () => h('div', { class: 'flex flex-col gap-4' }, blocs.map((bloc) => h(BlocRender, { key: bloc.id, bloc, donnees }))),
  });

  return renderToString(app);
};

const coque = ({ portfolio, pages, page, corps, titreDocument, robots }) => {
  const description = portfolio.description || `${portfolio.titre}, par ${portfolio.auteur}`;
  const theme = portfolio.theme ?? {};
  const entete = ENTETES.includes(theme.entete) ? theme.entete : 'clair';
  const logo = /^logo\.(png|jpg|webp)$/.test(portfolio.logo?.fichier ?? '')
    ? `<img class="pf-logo" src="${esc(portfolio.logo.fichier)}" alt="${esc(portfolio.logo.alt)}">`
    : '';
  const nav = pages.length > 1
    ? `<nav class="pf-nav" aria-label="Pages du portfolio">${pages.map((p) => `<a href="${esc(p.fichier)}"${p.fichier === page?.fichier ? ' aria-current="page"' : ''}>${esc(p.titre)}</a>`).join('')}</nav>`
    : '';

  return `<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(titreDocument)}</title>
<meta name="description" content="${esc(description)}">
<meta name="robots" content="${robots}">
<meta name="referrer" content="no-referrer">
<meta property="og:type" content="website">
<meta property="og:title" content="${esc(titreDocument)}">
<meta property="og:description" content="${esc(description)}">
<meta property="og:locale" content="fr_FR">
<link rel="stylesheet" href="portfolio.css">
</head>
<body class="pf-corps" style="${esc(variables(theme))}">
<header class="pf-entete pf-entete-${entete}"><div class="pf-conteneur">${logo}<div class="pf-identite"><p class="pf-auteur">${esc(portfolio.auteur)}</p><h1>${esc(portfolio.titre)}</h1></div>${nav}</div></header>
<main class="pf-conteneur">${corps}</main>
<footer class="pf-pied pf-conteneur"><small>Portfolio de compétences — ${esc(portfolio.auteur)}</small></footer>
</body>
</html>
`;
};

/**
 * @param {{ portfolio: object, pages: Array<{fichier: string, titre: string, blocs: object[]}>, donnees: object }} instantane
 * @returns {Promise<Record<string, string>>} fichier → HTML
 */
export const rendrePortfolio = async ({ portfolio, pages, donnees }) => {
  const robots = portfolio.indexable ? 'index,follow' : 'noindex,nofollow';
  const fichiers = {};
  const corpsPages = [];

  for (const page of pages) {
    const corps = await rendreBlocs(page.blocs, donnees);
    corpsPages.push({ page, corps });
    fichiers[page.fichier] = coque({
      portfolio, pages, page, corps, robots,
      titreDocument: pages.length > 1 ? `${page.titre} — ${portfolio.titre}` : portfolio.titre,
    });
  }

  // Version « une seule page » : base de l'export PDF.
  fichiers['print.html'] = coque({
    portfolio, pages: [], page: null, robots: 'noindex,nofollow', titreDocument: portfolio.titre,
    corps: corpsPages.map(({ page, corps }) => `<section class="pf-impression">${pages.length > 1 ? `<h2>${esc(page.titre)}</h2>` : ''}${corps}</section>`).join(''),
  });

  return fichiers;
};
