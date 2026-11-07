<script setup>
import { computed } from 'vue';
import { rendreMarkdown } from './markdown';
import { resoudreSource, tracesFiltrees, pourcentage } from './donnees';
import TraceCarte from '../components/portfolio/TraceCarte.vue';
import { couleurCompetence, urlIntegrable } from '../components/traces/options';

defineOptions({ name: 'BlocRender' });

/**
 * Rendu d'un bloc de portfolio (récursif). Ne contient aucun accès navigateur et n'interprète jamais de HTML
 * saisi : les textes passent par rendreMarkdown (échappement). Le même composant sert à l'aperçu, à l'éditeur
 * et au rendu public (lot 6). `donnees` fournit traces, progression, CV et parcours ; null dans un modèle.
 */
const props = defineProps({
  bloc: { type: Object, required: true },
  donnees: { type: Object, default: null },
});

const a = computed(() => props.bloc.attrs ?? {});
const enfants = computed(() => props.bloc.children ?? []);
const md = (texte) => rendreMarkdown(texte);
const source = computed(() => resoudreSource(props.donnees, a.value.source));
const images = computed(() => (a.value.images ?? []).map((s) => resoudreSource(props.donnees, s)).filter((s) => s.src));
const embed = computed(() => (props.bloc.type === 'embed' ? urlIntegrable(a.value.url) : null));
const urlSure = computed(() => (/^https?:\/\//i.test(a.value.url ?? '') ? a.value.url : null));
const trace = computed(() => props.donnees?.traces?.find((t) => t.uuid === a.value.trace) ?? null);
const traces = computed(() => tracesFiltrees(props.donnees, { competence: a.value.competence, maximum: a.value.maximum ?? 6 }));
const colonnes = computed(() => ({ gridTemplateColumns: (a.value.ratio ?? '1-1').split('-').map((n) => `${Number(n) || 1}fr`).join(' ') }));
const fonds = { aucun: '', doux: 'bg-surface-100 rounded-xl p-4', accent: 'bg-emerald-50 border border-emerald-200 rounded-xl p-4' };
const tons = {
  info: 'bg-sky-50 border-sky-300',
  success: 'bg-emerald-50 border-emerald-300',
  warning: 'bg-amber-50 border-amber-300',
  neutre: 'bg-surface-100 border-surface-300',
};
const cv = computed(() => props.donnees?.cv ?? null);
const mois = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' }) : '');
const periode = (e) => [mois(e.dateDebut), e.dateFin ? mois(e.dateFin) : e.dateDebut ? 'aujourd\'hui' : ''].filter(Boolean).join(' – ');
</script>

<template>
  <!-- Texte -->
  <component :is="`h${Math.min(Math.max(Number(a.niveau) || 2, 1), 4)}`" v-if="bloc.type === 'heading'" class="m-0 font-semibold">{{ a.texte }}</component>

  <div v-else-if="bloc.type === 'paragraph'" class="bloc-texte" v-html="md(a.texte)" />

  <blockquote v-else-if="bloc.type === 'quote'" class="m-0 border-l-4 border-emerald-500 pl-4 italic">
    <div class="bloc-texte" v-html="md(a.texte)" />
    <footer v-if="a.auteur" class="text-sm text-muted-color not-italic mt-1">— {{ a.auteur }}</footer>
  </blockquote>

  <component :is="a.style === 'number' ? 'ol' : 'ul'" v-else-if="bloc.type === 'list'" class="m-0 pl-6">
    <li v-for="(element, i) in a.elements ?? []" :key="i" v-text="element" />
  </component>

  <div v-else-if="bloc.type === 'callout'" class="rounded-lg border-l-4 p-3" :class="tons[a.ton] ?? tons.info">
    <div class="bloc-texte" v-html="md(a.texte)" />
  </div>

  <hr v-else-if="bloc.type === 'divider'" class="border-surface-200 my-2" />

  <a
    v-else-if="bloc.type === 'button'"
    :href="urlSure ?? undefined"
    target="_blank"
    rel="noopener noreferrer nofollow"
    class="inline-block px-4 py-2 rounded-lg bg-emerald-600 text-white no-underline font-medium"
  >{{ a.libelle }}</a>

  <!-- Médias -->
  <figure v-else-if="bloc.type === 'image'" class="m-0 flex flex-col gap-1">
    <img v-if="source.src" :src="source.src" :alt="a.alt || a.legende || source.nom || ''" class="rounded-lg max-w-full" loading="lazy" />
    <div v-else class="rounded-lg bg-surface-100 text-muted-color text-sm p-6 text-center">Image à choisir</div>
    <figcaption v-if="a.legende" class="text-sm text-muted-color">{{ a.legende }}</figcaption>
  </figure>

  <div v-else-if="bloc.type === 'gallery'" class="grid gap-2" :style="{ gridTemplateColumns: `repeat(${Number(a.colonnes) || 3}, minmax(0, 1fr))` }">
    <img v-for="(image, i) in images" :key="i" :src="image.src" alt="" class="rounded-lg w-full aspect-square object-cover" loading="lazy" />
    <div v-if="!images.length" class="col-span-full rounded-lg bg-surface-100 text-muted-color text-sm p-6 text-center">Galerie vide</div>
  </div>

  <div v-else-if="bloc.type === 'embed'">
    <iframe v-if="embed" :src="embed" :title="a.titre || 'Contenu intégré'" class="w-full aspect-video rounded-lg border-0" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups" allowfullscreen referrerpolicy="no-referrer" />
    <div v-else class="rounded-lg bg-surface-100 text-muted-color text-sm p-6 text-center">Adresse à renseigner</div>
  </div>

  <a v-else-if="bloc.type === 'file'" :href="source.telechargement ?? undefined" class="inline-flex items-center gap-2 text-primary">
    <i class="pi pi-download" /> {{ a.libelle || source.nom || 'Fichier à choisir' }}
  </a>

  <!-- Mise en page -->
  <div v-else-if="bloc.type === 'columns'" class="grid gap-4 items-start" :style="colonnes">
    <BlocRender v-for="c in enfants" :key="c.id" :bloc="c" :donnees="donnees" />
  </div>

  <div v-else-if="bloc.type === 'column'" class="flex flex-col gap-3 min-w-0">
    <BlocRender v-for="c in enfants" :key="c.id" :bloc="c" :donnees="donnees" />
  </div>

  <section v-else-if="bloc.type === 'group'" class="flex flex-col gap-3" :class="fonds[a.fond] ?? ''">
    <BlocRender v-for="c in enfants" :key="c.id" :bloc="c" :donnees="donnees" />
  </section>

  <div v-else-if="bloc.type === 'accordion'" class="flex flex-col gap-2">
    <BlocRender v-for="c in enfants" :key="c.id" :bloc="c" :donnees="donnees" />
  </div>

  <details v-else-if="bloc.type === 'panel'" :open="a.ouvert" class="rounded-lg border border-surface-200 p-3">
    <summary class="cursor-pointer font-medium">{{ a.titre }}</summary>
    <div class="flex flex-col gap-3 mt-3">
      <BlocRender v-for="c in enfants" :key="c.id" :bloc="c" :donnees="donnees" />
    </div>
  </details>

  <!-- Portfolio -->
  <template v-else-if="bloc.type === 'trace'">
    <TraceCarte v-if="trace" :trace="trace" :detail="a.affichage === 'detail'" />
    <div v-else class="rounded-lg bg-surface-100 text-muted-color text-sm p-6 text-center">Trace à choisir</div>
  </template>

  <div v-else-if="bloc.type === 'traces'">
    <div v-if="traces.length" :class="a.affichage === 'liste' ? 'flex flex-col gap-2' : 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3'">
      <TraceCarte v-for="t in traces" :key="t.uuid" :trace="t" />
    </div>
    <p v-else class="m-0 text-sm text-muted-color">Aucune trace pour le moment.</p>
  </div>

  <div v-else-if="bloc.type === 'progression'" class="flex flex-col gap-2">
    <template v-if="donnees?.progression?.length">
      <div v-for="p in donnees.progression" :key="p.competence.id" class="flex flex-col gap-1">
        <div class="flex justify-between text-sm">
          <span class="font-medium">{{ p.competence.nomCourt }}</span>
          <span class="text-muted-color">{{ p.couverts }} / {{ p.total }} niveau(x) démontré(s)</span>
        </div>
        <div v-if="a.affichage !== 'liste'" class="h-2 rounded-full bg-surface-200 overflow-hidden" role="progressbar" :aria-valuenow="pourcentage(p.couverts, p.total)" aria-valuemin="0" aria-valuemax="100">
          <div class="h-full rounded-full" :style="{ width: `${pourcentage(p.couverts, p.total)}%`, background: couleurCompetence(p.competence.couleur) }" />
        </div>
      </div>
    </template>
    <p v-else class="m-0 text-sm text-muted-color">La progression par compétence apparaîtra ici.</p>
  </div>

  <div v-else-if="bloc.type === 'cv'" class="flex flex-col gap-4">
    <template v-if="cv && (cv.poste || cv.experiences.length || cv.formations.length)">
      <div v-if="cv.poste || cv.description">
        <h3 v-if="cv.poste" class="m-0 text-lg font-semibold">{{ cv.poste }}</h3>
        <p v-if="cv.description" class="m-0 whitespace-pre-line">{{ cv.description }}</p>
      </div>
      <section v-if="a.experiences !== false && cv.experiences.length">
        <h4 class="mt-0 mb-2">Expériences</h4>
        <div v-for="(e, i) in cv.experiences" :key="i" class="mb-2">
          <strong>{{ e.poste }}</strong><span v-if="e.entreprise"> — {{ e.entreprise }}</span>
          <div class="text-sm text-muted-color">{{ periode(e) }}</div>
          <p v-if="e.description" class="m-0 text-sm whitespace-pre-line">{{ e.description }}</p>
        </div>
      </section>
      <section v-if="a.formations !== false && cv.formations.length">
        <h4 class="mt-0 mb-2">Formations</h4>
        <div v-for="(f, i) in cv.formations" :key="i" class="mb-2">
          <strong>{{ f.diplome }}</strong><span v-if="f.etablissement"> — {{ f.etablissement }}</span>
          <div class="text-sm text-muted-color">{{ periode(f) }}</div>
        </div>
      </section>
      <section v-if="a.competences !== false && (cv.hardSkills.length || cv.softSkills.length)">
        <h4 class="mt-0 mb-2">Compétences</h4>
        <div class="flex flex-wrap gap-1">
          <span v-for="s in [...cv.hardSkills, ...cv.softSkills]" :key="s" class="text-xs px-2 py-0.5 rounded-full bg-surface-100">{{ s }}</span>
        </div>
      </section>
      <section v-if="a.langues !== false && cv.langues.length">
        <h4 class="mt-0 mb-2">Langues</h4>
        <span v-for="l in cv.langues" :key="l.langue" class="mr-3 text-sm">{{ l.langue }}<span v-if="l.niveau" class="text-muted-color"> ({{ l.niveau }})</span></span>
      </section>
    </template>
    <p v-else class="m-0 text-sm text-muted-color">Votre CV apparaîtra ici. Complétez-le dans « Mon CV ».</p>
  </div>

  <ol v-else-if="bloc.type === 'parcours'" class="m-0 pl-0 list-none flex flex-col gap-2 border-l-2 border-emerald-300 ml-1">
    <li v-for="(p, i) in donnees?.parcours ?? []" :key="i" class="pl-4 relative">
      <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full" :class="p.actif ? 'bg-emerald-600' : 'bg-emerald-300'" />
      <strong>{{ p.annee }}</strong>
      <div class="text-sm text-muted-color">{{ [p.anneeUniversitaire, p.diplome].filter(Boolean).join(' · ') }}</div>
    </li>
    <li v-if="!donnees?.parcours?.length" class="pl-4 text-sm text-muted-color">Votre parcours apparaîtra ici.</li>
  </ol>

  <aside v-else-if="bloc.type === 'reflexion'" class="rounded-lg border border-violet-200 bg-violet-50/50 p-3 flex flex-col gap-1">
    <strong class="text-violet-800"><i class="pi pi-lightbulb" /> {{ a.titre }}</strong>
    <div v-if="a.texte" class="bloc-texte" v-html="md(a.texte)" />
    <p v-else class="m-0 text-sm text-muted-color">À rédiger.</p>
  </aside>
</template>

<style scoped>
.bloc-texte :deep(p) { margin: 0 0 0.5rem; }
.bloc-texte :deep(p:last-child) { margin-bottom: 0; }
.bloc-texte :deep(ul), .bloc-texte :deep(ol) { margin: 0 0 0.5rem; padding-left: 1.5rem; }
.bloc-texte :deep(code) { background: var(--p-content-hover-background); padding: 0 0.25rem; border-radius: 0.25rem; }
</style>
