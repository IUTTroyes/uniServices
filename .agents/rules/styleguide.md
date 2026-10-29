# Styleguide & Design System Frontend (UniServices)

Toutes les interfaces Vue 3 doivent respecter le système de design d'UniServices.

## Règle fondamentale : Toujours utiliser `@components`

Avant de concevoir du HTML ou des composants ad-hoc, exploitez les composants partagés dans `@components` (`shared/components/`) :

1. **En-têtes de page** : Utilisez toujours `HeaderComponent` (`:titre`, `:description`, `:icon`, `:color`, `:show-back`, `back-url`, et le slot `#actions`).
2. **Métriques et statistiques** : Utilisez `Kpi` (`:label`, `:value`, `:icon`, `:color`, `:description`, `:to`, `:loading`). Ne fabriquez pas de blocs KPI manuellement.
3. **Cartes conteneurs** : Utilisez `Card` (`:title`, `:subtitle`, `:icon`, `:color`, `:badge`, `#header-actions`).
4. **États vides** : Utilisez `EmptyState` (`:title`, `:description`, `:icon`, `:color`, `:compact`, `:action-label`, `@action`). À utiliser au niveau page et dans le slot `#empty` de DataTable avec `compact`.
5. **Indicateurs de chargement & Squelettes (Loaders)** :
   * **`ListSkeleton` / `SimpleSkeleton`** : À utiliser obligatoirement pour le chargement asynchrone des données de consultation (listes, tableaux, trombinoscopes, grilles de cartes, fiches et sélecteurs). Ils préservent l'empreinte spatiale (zéro Cumulative Layout Shift / saut de page) et améliorent significativement la vitesse perçue.
   * **`GlobalLoader`** : Réservé exclusivement aux opérations de traitement bloquantes (ex: synchronisation externe Apogée, export massif PDF/Excel, initialisation pleine page ou vérification de session).
   * **Règle absolue** : Ne jamais créer de spinner ad-hoc en CSS (`animate-spin`) ou de loader inline non standardisé.
6. **Boutons d'action verticaux** : Utilisez `ActionButtonVertical` dans `#actions` du Header ou les toolbars.
7. **Boutons avec confirmation** : Utilisez `ButtonDelete`, `ButtonSave`, `ButtonDuplicate` pour les actions critiques (intégration `ConfirmDialog`).
8. **Statuts & Badges** : Utilisez le composant PrimeVue `<Tag :value="..." :severity="..." />` avec les sévérités : `'success' | 'warn' | 'info' | 'danger' | 'secondary' | 'primary'`.
9. **Icônes polymorphes** : Les composants acceptent indifféremment les composants Heroicons (`@heroicons/vue/24/outline`) et les classes PrimeIcons (`pi pi-...`).
10. **Thème de couleur** : Utilisez les clés de thème : `'blue' | 'emerald' | 'amber' | 'purple' | 'indigo' | 'red' | 'gray' | 'teal' | 'orange'`.

Pour la documentation complète et les exemples de code, référez-vous à :
[docs/STYLEGUIDE_AI_AGENTS.md](../../docs/STYLEGUIDE_AI_AGENTS.md).
Démonstration interactive en local : `/auth/configuration/styleguide`.
