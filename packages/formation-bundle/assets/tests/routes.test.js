import { describe, it, expect } from 'vitest';
import { formationRoutes, anciennesAdresses } from '../router/routes.js';

const ancienne = (chemin) => anciennesAdresses.find((r) => r.path === `/intranet/${chemin}`);

describe('routes des formations', () => {
  it('redirige les anciennes adresses de l’intranet en gardant paramètres et requête', () => {
    const cible = ancienne('administration/annee/:anneeId/groupes/structure')
      .redirect({ params: { anneeId: '12' }, query: { a: '1' }, hash: '' });

    expect(cible).toEqual({ path: '/formation/annee/12/groupes/structure', query: { a: '1' }, hash: '' });
  });

  it('chaque redirection vise une route existante', () => {
    const chemins = [];
    const collecter = (routes, base) => routes.forEach((r) => {
      const chemin = [base, r.path].filter(Boolean).join('/');
      chemins.push(chemin);
      collecter(r.children || [], chemin);
    });
    collecter(formationRoutes, '/formation');

    anciennesAdresses.forEach((r) => {
      const params = Object.fromEntries([...r.path.matchAll(/:(\w+)/g)].map((m) => [m[1], `:${m[1]}`]));
      expect(chemins).toContain(r.redirect({ params, query: {}, hash: '' }).path);
    });
  });
});
