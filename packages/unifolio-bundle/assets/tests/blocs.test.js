import { describe, it, expect } from 'vitest';
import { rendreMarkdown, extraitTexte } from '../blocs/markdown';
import { TYPES, typesAutorises, groupesAutorises, nouveauBloc, dupliquer, identifiants, incomplets, attrsParDefaut } from '../blocs/schema';

describe('Markdown simplifié : aucun HTML saisi ne survit', () => {
  const attaques = [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '**<b onmouseover=alert(1)>gras</b>**',
    '[clic](javascript:alert(1))',
    '[clic](data:text/html;base64,PHNjcmlwdD4=)',
    '[clic](https://a.b" onmouseover="alert(1))',
    '`<script>`',
    '- <iframe src=//evil></iframe>',
    '"><svg onload=alert(1)>',
  ];

  const BALISES_AUTORISEES = ['p', 'br', 'strong', 'em', 'code', 'a', 'ul', 'ol', 'li'];

  it.each(attaques)('neutralise %s', (attaque) => {
    const html = rendreMarkdown(attaque);

    // Seules les balises produites par le rendu peuvent exister ; tout le reste est du texte échappé.
    const balises = [...html.matchAll(/<\/?([a-z0-9]+)/gi)].map((m) => m[1].toLowerCase());
    expect(balises.filter((b) => !BALISES_AUTORISEES.includes(b))).toEqual([]);
    // Un lien n'a que ses trois attributs, avec une adresse http(s).
    for (const a of html.matchAll(/<a\b([^>]*)>/gi)) {
      expect(a[1]).toMatch(/^ href="https?:\/\/[^"]*" target="_blank" rel="noopener noreferrer nofollow"$/);
    }
    expect(html).not.toMatch(/href="(?!https?:\/\/)/i);
  });

  it('met en forme gras, italique, code, liens http(s)', () => {
    expect(rendreMarkdown('**gras** et *italique* et `code`')).toBe('<p><strong>gras</strong> et <em>italique</em> et <code>code</code></p>');
    expect(rendreMarkdown('[IUT](https://iut-troyes.fr)')).toContain('href="https://iut-troyes.fr" target="_blank" rel="noopener noreferrer nofollow"');
  });

  it('ne met pas en forme à l\'intérieur d\'un bloc de code', () => {
    expect(rendreMarkdown('`**pas gras**`')).toBe('<p><code>**pas gras**</code></p>');
  });

  it('construit paragraphes, sauts de ligne et listes', () => {
    expect(rendreMarkdown('Un\nDeux\n\nTrois')).toBe('<p>Un<br>Deux</p><p>Trois</p>');
    expect(rendreMarkdown('- a\n- b')).toBe('<ul><li>a</li><li>b</li></ul>');
    expect(rendreMarkdown('1. a\n2. b')).toBe('<ol><li>a</li><li>b</li></ol>');
    expect(rendreMarkdown('Intro\n- a')).toBe('<p>Intro</p><ul><li>a</li></ul>');
  });

  it('gère le vide', () => {
    expect(rendreMarkdown('')).toBe('');
    expect(rendreMarkdown(null)).toBe('');
  });

  it('extrait un texte court sans mise en forme', () => {
    expect(extraitTexte('**Bonjour** [le monde](https://x.y)')).toBe('Bonjour le monde');
    expect(extraitTexte('a'.repeat(300), 20)).toHaveLength(20);
  });
});

describe('schéma des blocs partagé avec le serveur', () => {
  it('décrit les mêmes types que le serveur', () => {
    expect(Object.keys(TYPES)).toEqual(expect.arrayContaining(['heading', 'paragraph', 'image', 'gallery', 'embed', 'columns', 'column', 'accordion', 'panel', 'trace', 'traces', 'progression', 'cv', 'parcours', 'reflexion']));
  });

  it('propose à la racine tous les blocs sauf colonnes et volets', () => {
    const racine = typesAutorises();
    expect(racine).toContain('paragraph');
    expect(racine).toContain('columns');
    expect(racine).not.toContain('column');
    expect(racine).not.toContain('panel');
  });

  it('limite les enfants des colonnes et de l\'accordéon', () => {
    expect(typesAutorises('columns')).toEqual(['column']);
    expect(typesAutorises('accordion')).toEqual(['panel']);
    expect(typesAutorises('column')).toContain('image');
    expect(typesAutorises('divider')).toEqual([]);
  });

  it('regroupe les types pour le menu d\'ajout', () => {
    const groupes = groupesAutorises();
    expect(groupes.map((g) => g.cle)).toEqual(['texte', 'medias', 'mise_en_page', 'portfolio']);
    // Colonnes et volets s'ajoutent par un bouton dédié : ils n'apparaissent dans aucun groupe du menu.
    expect(groupesAutorises('columns')).toEqual([]);
  });

  it('crée des blocs avec leurs valeurs par défaut et leurs enfants initiaux', () => {
    const titre = nouveauBloc('heading');
    expect(titre.attrs).toEqual({ niveau: 2, texte: '' });
    expect(titre.id).toMatch(/^[A-Za-z0-9_-]{8,40}$/);

    const colonnes = nouveauBloc('columns');
    expect(colonnes.attrs.ratio).toBe('1-1');
    expect(colonnes.children.map((c) => c.type)).toEqual(['column', 'column']);
    expect(colonnes.children[0].children).toEqual([]);

    expect(() => nouveauBloc('script')).toThrow();
  });

  it('ne partage pas ses valeurs par défaut entre deux blocs', () => {
    const a = attrsParDefaut('list');
    a.elements.push('x');
    expect(attrsParDefaut('list').elements).toEqual(['']);
  });

  it('duplique avec de nouveaux identifiants à tous les niveaux', () => {
    const original = nouveauBloc('columns');
    const copie = dupliquer(original);
    const avant = identifiants([original]);
    const apres = identifiants([copie]);

    expect(apres).toHaveLength(avant.length);
    expect(apres.filter((id) => avant.includes(id))).toEqual([]);
    copie.attrs.ratio = '1-2';
    expect(original.attrs.ratio).toBe('1-1');
  });

  it('signale les champs obligatoires vides, y compris dans les conteneurs', () => {
    const colonnes = nouveauBloc('columns');
    colonnes.children[0].children.push(nouveauBloc('button'));
    const manques = incomplets([colonnes, nouveauBloc('divider')]).join(' | ');

    expect(manques).toContain('Adresse');
    expect(incomplets([nouveauBloc('divider')])).toEqual([]);
  });
});
