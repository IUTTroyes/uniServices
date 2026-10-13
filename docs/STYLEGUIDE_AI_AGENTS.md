# Guide du Styleguide & Système de Design pour Agents IA (UniServices)

Ce document est la référence absolue pour concevoir, refactorer et maintenir des interfaces utilisateur cohérentes, élégantes et accessibles au sein de l'application **UniServices** (Vue 3, PrimeVue 4, TailwindCSS).

Tous les agents IA intervenant sur le frontend doivent suivre scrupuleusement ces règles et privilégier **systématiquement** les composants partagés disponibles dans `@components`.

---

## 1. Principes Fondamentaux & Bonnes Pratiques

1. **Priorité aux composants partagés** : Avant de créer du balisage personnalisé, vérifiez toujours si un composant de `@components` (`shared/components/`) répond au besoin (`HeaderComponent`, `Card`, `Kpi`, `EmptyState`, `ActionButtonVertical`, etc.).
2. **Double compatibilité des icônes** : Les composants du design system acceptent de manière transparente :
   - Les composants **Heroicons** (`@heroicons/vue/24/outline` ou `@heroicons/vue/24/solid`).
   - Les chaînes de classe **PrimeIcons** (`pi pi-check`, `pi pi-user`, etc.).
3. **Thématisation unifiée (`color`)** : N'utilisez pas de classes Tailwind arbitraires de couleur (`text-red`, `bg-green-200`). Utilisez les clés de thème standardisées :
   `'blue' | 'green' | 'emerald' | 'yellow' | 'amber' | 'purple' | 'violet' | 'indigo' | 'red' | 'pink' | 'teal' | 'orange' | 'gray'`.
4. **Statuts et badges** : Utilisez toujours le composant PrimeVue `<Tag>` avec les sévérités standards (`'success' | 'warn' | 'info' | 'danger' | 'secondary' | 'primary'`).
5. **États vides (Empty States)** : Remplacez les messages bruts "Aucun résultat" par `<EmptyState>`. Utilisez la prop `compact` à l'intérieur des tableaux et des petites cartes.
6. **Actions verticales de header** : Les actions principales d'une page doivent être regroupées dans le slot `#actions` du `HeaderComponent` via `<ActionButtonVertical>`.

---

## 2. Bibliothèque des Composants Partagés (`@components`)

### 2.1. `HeaderComponent` — En-tête standardisé de page

Gère le titre de niveau 1, la description explicative, l'icône thématique, le bouton retour et la barre d'actions de page.

```vue
<script setup lang="ts">
import { ChartBarIcon, ArrowDownTrayIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { HeaderComponent, ActionButtonVertical } from '@components';
</script>

<template>
  <HeaderComponent
    titre="Titre de la Page"
    description="Description claire de la section et des fonctionnalités disponibles."
    :icon="ChartBarIcon"
    color="blue"
    :show-back="true"
    :back-url="{ name: 'dashboard' }"
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <ActionButtonVertical
          :icon="PlusIcon"
          label="Nouveau"
          severity="primary"
          @click="onCreate"
        />
        <ActionButtonVertical
          :icon="ArrowDownTrayIcon"
          label="Exporter"
          severity="secondary"
          @click="onExport"
        />
      </div>
    </template>
  </HeaderComponent>
</template>
```

#### Propriétés
| Propriété | Type | Défaut | Description |
|---|---|---|---|
| `titre` | `String` | *(Requis)* | Titre principal de la page. |
| `description` | `String` | *(Requis)* | Description ou consigne affichée sous le titre. |
| `icon` | `String \| Object` | `null` | Classe PrimeIcons (`'pi pi-table'`) ou composant Heroicons. |
| `color` | `String` | `''` | Thème de couleur de l'icône (`'blue'`, `'indigo'`, `'emerald'`, etc.). |
| `showBack` | `Boolean` | `true` | Affiche ou masque le bouton "Retour". |
| `backUrl` | `String \| Object` | `null` | Route ou URL pour le retour. Si absent, utilise l'historique (`router.go(-1)`). |

#### Slots
- `#actions` : Boutons d'actions principaux situés à l'extrémité droite de l'en-tête (à côté du bouton Retour).

---

### 2.2. `Kpi` — Cartes de métriques & indicateurs clés

Affiche un indicateur statistique avec icône, couleur thématique, valeur grand format et description optionnelle.

```vue
<script setup lang="ts">
import { UserGroupIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';
import { Kpi } from '@components';
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <Kpi
      label="Total Participants"
      :value="142"
      :icon="UserGroupIcon"
      color="blue"
      :to="{ name: 'analytics_view' }"
    />
    <Kpi
      label="Taux de complétion"
      value="85.4%"
      :icon="CheckCircleIcon"
      color="emerald"
      description="+4.2% par rapport au mois dernier"
    />
    <Kpi
      label="Temps moyen"
      value="6 min"
      :icon="ClockIcon"
      color="amber"
    />
    <Kpi
      label="En attente"
      :value="18"
      icon="pi pi-hourglass"
      color="purple"
      :loading="isLoading"
    />
  </div>
</template>
```

#### Propriétés
| Propriété | Type | Défaut | Description |
|---|---|---|---|
| `label` | `String` | *(Requis)* | Libellé supérieur de la statistique. |
| `value` | `String \| Number` | *(Requis)* | Valeur mise en valeur. |
| `icon` | `String \| Object` | `null` | Heroicon ou classe PrimeIcons. |
| `color` | `String` | `'blue'` | Thème de couleur (`'blue'`, `'emerald'`, `'purple'`, `'amber'`, etc.). |
| `description` | `String` | `''` | Texte informatif ou indicateur de tendance sous la valeur. |
| `loading` | `Boolean` | `false` | Affiche un `SimpleSkeleton` au lieu de la valeur si `true`. |
| `to` | `String \| Object` | `undefined` | Transforme le KPI en lien Vue Router interactif avec micro-animations au survol. |

---

### 2.3. `Card` — Boîte conteneur universelle

Conteneur de référence pour les sections, les tableaux et les formulaires.

```vue
<script setup lang="ts">
import { Card } from '@components';
</script>

<template>
  <Card
    title="Liste des réponses"
    subtitle="124 réponses enregistrées"
    icon="pi pi-table"
    color="blue"
    badge="Actif"
    badge-severity="success"
    body-class="p-4 sm:p-6"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="searchQuery" placeholder="Rechercher..." class="text-xs" />
        </IconField>
      </div>
    </template>

    <!-- Contenu principal -->
    <DataTable :value="items">...</DataTable>
  </Card>
</template>
```

#### Propriétés
| Propriété | Type | Défaut | Description |
|---|---|---|---|
| `title` | `String` | `''` | Titre de l'en-tête de carte. |
| `subtitle` | `String` | `''` | Sous-titre explicatif sous le titre. |
| `icon` | `String \| Object` | `null` | Icône d'en-tête (PrimeIcons ou Heroicons). |
| `color` | `String` | `''` | Thème de couleur de l'icône. |
| `badge` | `String` | `''` | Texte d'un badge à droite. |
| `badgeSeverity` | `String` | `'secondary'` | Sévérité du badge (`'success'`, `'warn'`, etc.). |
| `bodyClass` | `String` | `''` | Classes CSS appliquées à la zone intérieure (`card-body`). |

#### Slots
- `#header-actions` : Contrôles (recherche, filtres rapides, boutons) situés à droite de l'en-tête, sans remplacer le titre ni l'icône.
- `#header` : Remplacement complet de l'en-tête si une personnalisation totale est requise.
- `#default` : Contenu principal de la carte.

---

### 2.4. `EmptyState` — Placeholder d'absence de données

Ne créez plus d'état vide maison avec du texte centré basique. Utilisez `<EmptyState>`.

```vue
<script setup lang="ts">
import { EmptyState } from '@components';
</script>

<!-- Au niveau de la page -->
<EmptyState
  v-if="items.length === 0"
  title="Aucun participant invité"
  description="Commencez par ajouter des participants pour lancer la collecte des réponses."
  icon="pi pi-users"
  color="blue"
  action-label="Inviter des participants"
  action-icon="pi pi-user-plus"
  @action="openInviteModal"
/>

<!-- Dans le slot #empty d'un DataTable -->
<DataTable :value="items">
  <template #empty>
    <EmptyState
      title="Aucun résultat trouvé"
      description="Aucune ligne ne correspond aux filtres actuels."
      icon="pi pi-search"
      color="gray"
      compact
    />
  </template>
</DataTable>
```

#### Propriétés
| Propriété | Type | Défaut | Description |
|---|---|---|---|
| `title` | `String` | `'Aucune donnée disponible'` | Titre mis en avant. |
| `description` | `String` | `''` | Texte d'explication ou conseil d'action. |
| `icon` | `String \| Object` | `'pi pi-inbox'` | Icône centrale (Heroicons ou PrimeIcons). |
| `color` | `String` | `'gray'` | `'gray' \| 'blue' \| 'emerald' \| 'amber' \| 'violet' \| 'rose' \| 'indigo'`. |
| `compact` | `Boolean` | `false` | Version réduite idéale pour tableaux (`#empty`) et petites cartes. |
| `actionLabel` | `String` | `''` | Texte du bouton d'action principal. |
| `actionIcon` | `String \| Object` | `''` | Icône du bouton d'action. |

#### Événements
- `@action` : Émis lors du clic sur le bouton d'action.

---

### 2.5. `ActionButtonVertical` — Boutons d'action modernes

Idéal dans les barres d'actions et les en-têtes de page.

```vue
<ActionButtonVertical
  :to="{ name: 'analytics_view', params: { id: surveyId } }"
  :icon="ChartBarIcon"
  label="Statistiques"
  severity="help"
/>
<ActionButtonVertical
  :icon="UserPlusIcon"
  label="Inviter"
  severity="primary"
  @click="showInvite = true"
/>
<ActionButtonVertical
  icon="pi pi-download"
  label="Exporter"
  severity="secondary"
  :disabled="items.length === 0"
  @click="exportCsv"
/>
```

#### Sévérités disponibles
- `'primary'` (Orange/Accent)
- `'secondary'` (Gris neutre)
- `'success'` (Émeraude)
- `'info'` (Bleu)
- `'warning'` (Ambre)
- `'help'` (Violet/Mauve)
- `'danger'` (Rouge)

---

### 2.6. Boutons d'Action & Confirmation (`Buttons/`)

Utilisez les boutons avec dialogue de confirmation intégré pour éviter les suppressions accidentelles :

```vue
<script setup lang="ts">
import { ButtonDelete, ButtonSave, ButtonEdit, ButtonInfo, ButtonDuplicate } from '@components';
</script>

<template>
  <!-- Suppression avec dialogue de confirmation PrimeVue automatique -->
  <ButtonDelete
    tooltip="Supprimer cet élément"
    label="Supprimer"
    @confirm-delete="onDelete(item)"
  />

  <!-- Modification -->
  <ButtonEdit tooltip="Modifier l'élément" @click="onEdit(item)" />

  <!-- Sauvegarde avec confirmation optionnelle -->
  <ButtonSave tooltip="Enregistrer les modifications" @confirm-save="onSave" />

  <!-- Duplication -->
  <ButtonDuplicate tooltip="Dupliquer" @confirm-duplicate="onDuplicate" />

  <!-- Information -->
  <ButtonInfo tooltip="Détails" @click="onInfo" />
</template>
```

---

### 2.7. `ApplicationBadge` — Badge d'application avec couleurs du Manifeste

Affiche un badge thématique standardisé représentant une application du portail UniServices, en appliquant rigoureusement les couleurs et icônes officielles configurées dans `manifest.ts`.

```vue
<script setup lang="ts">
import { ApplicationBadge } from '@components';
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <!-- Puce par défaut -->
    <ApplicationBadge app="intranet" />

    <!-- Avec icône PrimeIcon de l'application -->
    <ApplicationBadge app="stage" show-icon />

    <!-- Avec indicateur point (dot) -->
    <ApplicationBadge app="questionnaire" show-dot />

    <!-- Format pilule (rounded-full) -->
    <ApplicationBadge app="helpdesk" rounded show-icon />

    <!-- Tailles disponibles : xs, sm (défaut), md -->
    <ApplicationBadge app="documents" size="xs" />
    <ApplicationBadge app="unifolio" size="sm" show-icon />
    <ApplicationBadge app="finance" size="md" show-dot />
  </div>
</template>
```

#### Propriétés
| Propriété | Type | Défaut | Description |
|---|---|---|---|
| `app` | `String \| Object` | *(Requis)* | Clé de l'application (`'intranet'`, `'stage'`, `'questionnaire'`, `'documents'`, `'helpdesk'`, `'unifolio'`, `'finance'`, `'auth'`) ou objet contenant un identifiant. |
| `size` | `String` | `'sm'` | Taille d'affichage : `'xs'` (compact/tableaux), `'sm'` (standard), `'md'` (confort). |
| `showIcon` | `Boolean` | `false` | Affiche l'icône PrimeIcon associée à l'application. |
| `showDot` | `Boolean` | `false` | Affiche une pastille ronde colorée (dot) à gauche du libellé. |
| `rounded` | `Boolean` | `false` | Style de bordure : `false` pour puce (`rounded-md`), `true` pour pilule (`rounded-full`). |

#### Couleurs officielles (selon `manifest.ts`)
| Application | Clé | Couleur principale |
|---|---|---|
| Intranet | `intranet` | Violet (`violet`) |
| Gestion des stages | `stage` | Sarcelle (`teal`) |
| Questionnaires & Qualité | `questionnaire` | Vert (`green`) |
| Gestion des documents | `documents` | Bleu (`blue`) |
| Assistance & Helpdesk | `helpdesk` | Orange (`orange`) |
| Portefeuille de compétences UniFolio | `unifolio` | Émeraude (`emerald`) |
| Gestion financière | `finance` | Émeraude (`emerald`) |
| Portail & Authentification | `auth` | Jaune (`yellow`) |

---

## 3. Conventions de Tableaux (`DataTable` PrimeVue)

Pour toutes les pages de liste et de gestion :
1. **Sélection multiple & Actions de masse** :
   - Ajoutez `<Column selectionMode="multiple" headerStyle="width: 3.5rem" />`.
   - Fournissez une barre de sélection contextuelle animée (`transition name="slide-down"`) quand `selectedItems.length > 0`.
   - Fournissez des raccourcis "Tout cocher / Tout décocher" et des sélections ciblées (ex: "Cocher ceux à relancer").
2. **Statuts** :
   - N'utilisez pas de classes Tailwind en dur (`bg-green-100 text-green-800`).
   - Utilisez `<Tag :value="item.statusLabel" :severity="getStatusSeverity(item)" />`.
3. **Boutons d'action par ligne** :
   - Privilégiez `<Button text size="small" icon="..." label="..." severity="..." />` pour un rendu net et uniforme.
4. **Filtres globaux & Recherche** :
   - Intégrez un `IconField` avec `InputIcon` et `InputText` dans le `#header-actions` de la carte ou le `#header` du DataTable.

---

## 4. Nuancier et Palette Thématique

| Clé | Fond d'accent (`bg`) | Texte (`text`) | Bordure (`border`) | Usage recommandé |
|---|---|---|---|---|
| `blue` | `bg-blue-50 dark:bg-blue-950/20` | `text-blue-600 dark:text-blue-400` | `border-blue-100 dark:border-blue-900/30` | Données générales, participants, listes |
| `emerald` / `green` | `bg-emerald-50 dark:bg-emerald-950/20` | `text-emerald-600 dark:text-emerald-400` | `border-emerald-100 dark:border-emerald-900/30` | Succès, validé, complété, publié |
| `amber` / `yellow` | `bg-amber-50 dark:bg-amber-950/20` | `text-amber-600 dark:text-amber-400` | `border-amber-100 dark:border-amber-900/30` | En cours, en attente, temps, rappel |
| `purple` / `violet` | `bg-purple-50 dark:bg-purple-950/20` | `text-purple-600 dark:text-purple-400` | `border-purple-100 dark:border-purple-900/30` | Taux, pourcentages, analytique |
| `indigo` | `bg-indigo-50 dark:bg-indigo-950/20` | `text-indigo-600 dark:text-indigo-400` | `border-indigo-100 dark:border-indigo-900/30` | Rôles, permissions, administration |
| `red` | `bg-red-50 dark:bg-red-950/20` | `text-red-600 dark:text-red-400` | `border-red-100 dark:border-red-900/30` | Erreur, fermé, supprimé, critique |
| `gray` | `bg-gray-50 dark:bg-gray-950/20` | `text-gray-600 dark:text-gray-400` | `border-gray-100 dark:border-gray-900/30` | Inactif, non commencé, brouillon |

---

## 5. Ce qu'un Agent IA ne doit JAMAIS faire (Anti-Patterns)

❌ **Anti-pattern 1** : Créer un en-tête avec un `<h1>` nu et un bouton retour fait main.  
👉 **Règle** : Toujours utiliser `<HeaderComponent :titre="..." :description="..." :show-back="true" />`.

❌ **Anti-pattern 2** : Fabriquer des blocs de statistiques en `div.card` avec des classes de couleur arbitraires non réactives.  
👉 **Règle** : Toujours utiliser `<Kpi label="..." :value="..." :icon="..." color="..." />`.

❌ **Anti-pattern 3** : Rédiger un état vide en simple texte `<div class="text-center">Aucun résultat</div>`.  
👉 **Règle** : Toujours utiliser `<EmptyState title="..." description="..." icon="..." :compact="..." />`.

❌ **Anti-pattern 4** : Laisser des backticks dans une chaîne de prop statique (ex: `description="`Texte`"`).  
👉 **Règle** : Écrire `description="Texte"` ou `:description="`Texte ${valeur}`"`.

❌ **Anti-pattern 5** : Réinventer des boîtes de dialogue de confirmation de suppression avec des `confirm()` natifs du navigateur.  
👉 **Règle** : Utiliser `<ButtonDelete @confirm-delete="..." />` avec `<ConfirmDialog />` de PrimeVue.

❌ **Anti-pattern 6** : Coder des badges d'applications personnalisés en HTML brut (ex: `<span class="bg-blue-100 text-blue-800">Stages</span>`).  
👉 **Règle** : Toujours utiliser `<ApplicationBadge :app="..." />` pour garantir l'application fidèle des couleurs et icônes officielles de `manifest.ts`.

---

## 6. Page de Démonstration Interactive

Pour visualiser, tester et copier les extraits de code en direct, rendez-vous dans l'application sur la route du styleguide :
`http://localhost:3000/auth/configuration/styleguide` (composant `StyleguideView.vue`).

