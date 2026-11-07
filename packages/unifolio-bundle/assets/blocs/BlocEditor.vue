<script setup>
import { computed, inject, provide, ref } from 'vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import Button from 'primevue/button';
import Popover from 'primevue/popover';
import BlocRender from './BlocRender.vue';
import BlocInspecteur from '../components/portfolio/BlocInspecteur.vue';
import { TYPES, groupesAutorises, icone, libelleType, nouveauBloc, dupliquer } from './schema';

defineOptions({ name: 'BlocEditor' });

/**
 * Éditeur d'une liste de blocs (récursif pour colonnes, sections et accordéons).
 * Les blocs sont modifiés en place (le parent détient le tableau réactif) ; chaque modification émet « change ».
 * En mode modèle, chaque bloc peut être verrouillé (imposé aux étudiants) ; sinon un bloc imposé ne se supprime pas.
 */
const props = defineProps({
  blocs: { type: Array, required: true },
  parentType: { type: String, default: null },
  donnees: { type: Object, default: null },
  verrouilles: { type: Array, default: () => [] },
  modeModele: { type: Boolean, default: false },
  lectureSeule: { type: Boolean, default: false },
});
const emit = defineEmits(['change']);

const injectee = inject('selectionBloc', null);
const selection = injectee ?? ref(null);
if (!injectee) provide('selectionBloc', selection);

const menu = ref(null);
const cible = ref(null);
const groupes = computed(() => groupesAutorises(props.parentType));
const estVerrouille = (b) => props.verrouilles.includes(b.id);
const protege = (b) => estVerrouille(b) && !props.modeModele;
const aDesReglages = (b) => Object.keys(TYPES[b.type]?.attrs ?? {}).length > 0;
const changement = () => emit('change');

const ouvrirMenu = (event, index = null) => {
  cible.value = index;
  menu.value.toggle(event);
};
const ajouter = (type) => {
  const bloc = nouveauBloc(type);
  props.blocs.splice(cible.value === null ? props.blocs.length : cible.value, 0, bloc);
  selection.value = bloc.id;
  menu.value.hide();
  changement();
};
const ajouterVolet = () => {
  const volet = nouveauBloc('panel');
  props.blocs.push(volet);
  selection.value = volet.id;
  changement();
};
const deplacer = (i, delta) => {
  const j = i + delta;
  if (j < 0 || j >= props.blocs.length) return;
  const [b] = props.blocs.splice(i, 1);
  props.blocs.splice(j, 0, b);
  changement();
};
const copier = (i) => {
  props.blocs.splice(i + 1, 0, dupliquer(props.blocs[i]));
  changement();
};
const retirer = (i) => {
  if (selection.value === props.blocs[i].id) selection.value = null;
  props.blocs.splice(i, 1);
  changement();
};
const basculerVerrou = (b) => {
  const i = props.verrouilles.indexOf(b.id);
  if (i >= 0) props.verrouilles.splice(i, 1); else props.verrouilles.push(b.id);
  changement();
};

/** Le nombre de colonnes suit la répartition choisie ; les blocs des colonnes retirées passent dans la dernière. */
const ajusterColonnes = (b) => {
  const n = (b.attrs.ratio ?? '1-1').split('-').length;
  while (b.children.length < n) b.children.push(nouveauBloc('column'));
  if (b.children.length > n) {
    const retirees = b.children.splice(n);
    for (const c of retirees) b.children[n - 1].children.push(...c.children);
  }
};
const majAttrs = (b, attrs) => {
  b.attrs = attrs;
  if (b.type === 'columns') ajusterColonnes(b);
  changement();
};
const grille = (b) => ({ gridTemplateColumns: (b.attrs.ratio ?? '1-1').split('-').map((n) => `${Number(n) || 1}fr`).join(' ') });
const fonds = { aucun: 'border border-dashed border-surface-300', doux: 'bg-surface-100', accent: 'bg-emerald-50 border border-emerald-200' };
</script>

<template>
  <div class="flex flex-col gap-2">
    <p v-if="blocs.length === 0 && parentType === null" class="m-0 p-6 text-center text-muted-color border border-dashed border-surface-300 rounded-lg">
      Cette page est vide. Ajoutez votre premier bloc.
    </p>

    <draggable :list="blocs" group="blocs" handle=".bloc-poignee" :animation="150" :disabled="lectureSeule" class="flex flex-col gap-2" @change="changement">
      <div
        v-for="(b, i) in blocs"
        :key="b.id"
        role="group"
        :aria-label="libelleType(b.type)"
        class="rounded-lg border bg-white dark:bg-surface-900 transition-shadow"
        :class="selection === b.id ? 'border-emerald-500 shadow-md' : 'border-surface-200 hover:border-surface-300'"
        @click.stop="selection = b.id"
      >
        <div class="flex items-center gap-1 px-2 py-1 text-xs text-muted-color border-b border-surface-100">
          <i v-if="!lectureSeule" class="pi pi-bars bloc-poignee cursor-move p-1" title="Déplacer" aria-hidden="true" />
          <i :class="icone(b.type)" aria-hidden="true" />
          <span class="font-medium">{{ libelleType(b.type) }}</span>
          <i v-if="estVerrouille(b)" class="pi pi-lock text-amber-600" :title="modeModele ? 'Bloc imposé aux étudiants' : 'Bloc imposé par le modèle : son contenu reste modifiable'" />
          <span v-if="!lectureSeule" class="ml-auto flex items-center">
            <Button icon="pi pi-arrow-up" size="small" text rounded severity="secondary" :disabled="i === 0" aria-label="Monter" @click.stop="deplacer(i, -1)" />
            <Button icon="pi pi-arrow-down" size="small" text rounded severity="secondary" :disabled="i === blocs.length - 1" aria-label="Descendre" @click.stop="deplacer(i, 1)" />
            <Button v-if="parentType !== 'columns' && parentType !== 'accordion'" icon="pi pi-plus" size="small" text rounded severity="secondary" aria-label="Insérer un bloc dessous" @click.stop="ouvrirMenu($event, i + 1)" />
            <Button v-if="modeModele" :icon="estVerrouille(b) ? 'pi pi-lock' : 'pi pi-lock-open'" size="small" text rounded :severity="estVerrouille(b) ? 'warn' : 'secondary'" :aria-label="estVerrouille(b) ? 'Ne plus imposer ce bloc' : 'Imposer ce bloc'" @click.stop="basculerVerrou(b)" />
            <template v-if="!protege(b) && b.type !== 'column'">
              <Button v-if="b.type !== 'panel'" icon="pi pi-copy" size="small" text rounded severity="secondary" aria-label="Dupliquer" @click.stop="copier(i)" />
              <Button icon="pi pi-trash" size="small" text rounded severity="danger" aria-label="Supprimer le bloc" @click.stop="retirer(i)" />
            </template>
          </span>
        </div>

        <div class="p-3 flex flex-col gap-3">
          <BlocInspecteur v-if="selection === b.id && !lectureSeule && aDesReglages(b)" :bloc="b" :donnees="donnees" @update:attrs="(attrs) => majAttrs(b, attrs)" />

          <!-- Conteneurs : leurs enfants s'éditent sur place -->
          <div v-if="b.type === 'columns'" class="grid gap-3 items-start" :style="grille(b)">
            <div v-for="c in b.children" :key="c.id" class="rounded-lg border border-dashed border-surface-300 p-2 min-h-16">
              <BlocEditor :blocs="c.children" parent-type="column" :donnees="donnees" :verrouilles="verrouilles" :mode-modele="modeModele" :lecture-seule="lectureSeule" @change="changement" />
            </div>
          </div>

          <div v-else-if="b.type === 'group'" class="rounded-lg p-3" :class="fonds[b.attrs.fond] ?? fonds.aucun">
            <BlocEditor :blocs="b.children" parent-type="group" :donnees="donnees" :verrouilles="verrouilles" :mode-modele="modeModele" :lecture-seule="lectureSeule" @change="changement" />
          </div>

          <div v-else-if="b.type === 'accordion'" class="flex flex-col gap-2">
            <BlocEditor :blocs="b.children" parent-type="accordion" :donnees="donnees" :verrouilles="verrouilles" :mode-modele="modeModele" :lecture-seule="lectureSeule" @change="changement" />
          </div>

          <div v-else-if="b.type === 'panel'" class="flex flex-col gap-2">
            <strong class="text-sm">{{ b.attrs.titre || 'Volet sans titre' }}</strong>
            <BlocEditor :blocs="b.children" parent-type="panel" :donnees="donnees" :verrouilles="verrouilles" :mode-modele="modeModele" :lecture-seule="lectureSeule" @change="changement" />
          </div>

          <!-- Les autres blocs s'affichent tels qu'ils seront rendus -->
          <BlocRender v-else :bloc="b" :donnees="donnees" />
        </div>
      </div>
    </draggable>

    <div v-if="!lectureSeule" class="flex">
      <Button v-if="parentType === 'accordion'" label="Ajouter un volet" icon="pi pi-plus" size="small" text @click.stop="ajouterVolet" />
      <Button v-else-if="parentType !== 'columns'" label="Ajouter un bloc" icon="pi pi-plus" size="small" text @click.stop="ouvrirMenu($event)" />
    </div>

    <Popover ref="menu">
      <div class="flex flex-col gap-3 w-72 max-h-96 overflow-auto">
        <section v-for="g in groupes" :key="g.cle" class="flex flex-col gap-1">
          <h5 class="m-0 text-xs uppercase tracking-wide text-muted-color">{{ g.libelle }}</h5>
          <div class="grid grid-cols-2 gap-1">
            <button
              v-for="t in g.types"
              :key="t.type"
              type="button"
              class="flex items-center gap-2 p-2 rounded-lg border border-surface-200 bg-transparent cursor-pointer text-left text-sm hover:bg-surface-100"
              @click="ajouter(t.type)"
            >
              <i :class="t.icone" aria-hidden="true" /> {{ t.libelle }}
            </button>
          </div>
        </section>
      </div>
    </Popover>
  </div>
</template>
