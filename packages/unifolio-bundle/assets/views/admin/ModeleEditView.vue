<script setup>
import { computed, onBeforeUnmount, onMounted, provide, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import BlocEditor from '../../blocs/BlocEditor.vue';
import { copie, nouveauBloc } from '../../blocs/schema';
import { modeleService, messagesErreur } from '../../requests/portfolioService';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const modele = ref(null);
const pages = ref([]);
const meta = reactive({ libelle: '', description: '', actif: true });
const courante = ref(0);
const chargement = ref(true);
const erreurChargement = ref(null);
const enregistrement = ref(false);
const dirty = ref(false);
const erreurs = ref([]);
const selection = ref(null);
provide('selectionBloc', selection);
let cle = 0;

const lectureSeule = computed(() => !modele.value?.modifiable);
const page = computed(() => pages.value[courante.value] ?? null);

const verser = (m) => {
  modele.value = m;
  Object.assign(meta, { libelle: m.libelle, description: m.description ?? '', actif: m.actif });
  pages.value = m.pages.map((p) => ({ cle: ++cle, titre: p.titre, verrouillee: !!p.verrouillee, blocs: copie(p.blocs), verrouilles: [...(p.verrouilles ?? [])] }));
  courante.value = Math.min(courante.value, Math.max(pages.value.length - 1, 0));
  dirty.value = false;
  selection.value = null;
};

const avantQuitter = (event) => { if (dirty.value) { event.preventDefault(); event.returnValue = ''; } };
onMounted(async () => {
  window.addEventListener('beforeunload', avantQuitter);
  try {
    const { data } = await modeleService.lire(route.params.uuid);
    verser(data);
  } catch (e) {
    erreurChargement.value = messagesErreur(e, 'Modèle introuvable.')[0];
  } finally {
    chargement.value = false;
  }
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', avantQuitter));

const modifie = () => { dirty.value = true; };
const aller = (i) => { courante.value = i; selection.value = null; };
const ajouterPage = () => {
  pages.value.push({ cle: ++cle, titre: `Page ${pages.value.length + 1}`, verrouillee: false, blocs: [nouveauBloc('heading')], verrouilles: [] });
  aller(pages.value.length - 1);
  modifie();
};
const supprimerPage = (i) => confirm.require({
  header: 'Supprimer la page',
  message: `Supprimer la page « ${pages.value[i].titre} » du modèle ?`,
  icon: 'pi pi-trash',
  acceptProps: { label: 'Supprimer', severity: 'danger' },
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  accept: () => { pages.value.splice(i, 1); aller(Math.min(courante.value, pages.value.length - 1)); modifie(); },
});

const enregistrer = async () => {
  enregistrement.value = true;
  erreurs.value = [];
  try {
    await modeleService.modifier(modele.value.uuid, { libelle: meta.libelle, description: meta.description, actif: meta.actif });
    const { data } = await modeleService.definirStructure(modele.value.uuid, pages.value.map(({ titre, verrouillee, blocs, verrouilles }) => ({ titre, verrouillee, blocs, verrouilles })));
    verser(data);
    toast.add({ severity: 'success', summary: 'Modèle enregistré', life: 3000 });
  } catch (e) {
    erreurs.value = messagesErreur(e);
  } finally {
    enregistrement.value = false;
  }
};

const nbImposes = (p) => p.verrouilles.length + (p.verrouillee ? 1 : 0);
</script>

<template>
  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreurChargement" icon="pi pi-exclamation-triangle" color="rose" title="Modèle indisponible" :description="erreurChargement" />

  <template v-else-if="modele">
    <HeaderComponent
      icon="pi pi-clone"
      :titre="modele.libelle"
      :description="modele.libellePortee"
      :show-back="true"
      back-url="/unifolio/admin/modeles"
      color="emerald"
    >
      <template #actions>
        <Tag v-if="dirty" value="Modifications non enregistrées" severity="warn" icon="pi pi-pencil" />
        <Button v-if="!lectureSeule" label="Enregistrer" icon="pi pi-save" :disabled="!dirty" :loading="enregistrement" @click="enregistrer" />
      </template>
    </HeaderComponent>

    <Message v-if="lectureSeule" severity="info" :closable="false">
      Modèle en lecture seule. <Button label="Adapter pour mon département" icon="pi pi-copy" size="small" text @click="modeleService.dupliquer(modele.uuid, { portee: 'departement' }).then(({ data }) => router.push(`/unifolio/admin/modeles/${data.uuid}`))" />
    </Message>
    <Message v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
      <ul class="m-0 pl-4"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
    </Message>

    <section class="card p-4 grid gap-3 md:grid-cols-3">
      <div class="flex flex-col gap-1">
        <label for="mo-libelle" class="font-medium">Libellé</label>
        <InputText id="mo-libelle" v-model="meta.libelle" :disabled="lectureSeule" @input="modifie" />
      </div>
      <div class="flex flex-col gap-1 md:col-span-2">
        <label for="mo-desc" class="font-medium">Description</label>
        <Textarea id="mo-desc" v-model="meta.description" rows="2" auto-resize :disabled="lectureSeule" @input="modifie" />
      </div>
      <label class="flex items-center gap-2 text-sm cursor-pointer"><ToggleSwitch v-model="meta.actif" :disabled="lectureSeule" @update:model-value="modifie" /> Modèle actif (proposé aux campagnes)</label>
    </section>

    <Message severity="secondary" :closable="false">
      Les étudiants reçoivent une <strong>copie</strong> du modèle : le modifier n'affecte pas les portfolios déjà créés.
      Une <i class="pi pi-lock" /> <strong>page imposée</strong> ne peut être ni supprimée ni renommée ; un <i class="pi pi-lock" /> <strong>bloc imposé</strong> ne peut être ni supprimé ni changé de type (son contenu reste libre).
      Les traces, images et fichiers personnels ne peuvent pas figurer dans un modèle.
    </Message>

    <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)] items-start">
      <aside class="card p-3 flex flex-col gap-2 lg:sticky lg:top-2" aria-label="Pages du modèle">
        <h3 class="text-sm font-semibold m-0">Pages</h3>
        <draggable :list="pages" handle=".page-poignee" :disabled="lectureSeule" class="flex flex-col gap-1" @end="modifie">
          <div v-for="(p, i) in pages" :key="p.cle" class="flex items-center gap-1 rounded-lg px-1" :class="i === courante ? 'bg-emerald-50 dark:bg-emerald-950/30' : ''">
            <i v-if="!lectureSeule" class="pi pi-bars page-poignee cursor-move text-muted-color p-1" aria-hidden="true" />
            <button type="button" class="flex-1 text-left bg-transparent border-0 py-2 cursor-pointer text-sm truncate" :class="i === courante ? 'font-semibold' : ''" :aria-current="i === courante ? 'page' : undefined" @click="aller(i)">{{ p.titre || 'Sans titre' }}</button>
            <i v-if="nbImposes(p)" class="pi pi-lock text-amber-600 text-xs" :title="`${nbImposes(p)} élément(s) imposé(s)`" />
            <Button v-if="!lectureSeule && pages.length > 1" icon="pi pi-trash" size="small" text rounded severity="danger" :aria-label="`Supprimer ${p.titre}`" @click="supprimerPage(i)" />
          </div>
        </draggable>
        <Button v-if="!lectureSeule && pages.length < 20" label="Ajouter une page" icon="pi pi-plus" size="small" outlined @click="ajouterPage" />
      </aside>

      <section v-if="page" class="flex flex-col gap-3 min-w-0">
        <div class="card p-3 flex flex-wrap items-center gap-3">
          <InputText v-model="page.titre" maxlength="120" class="flex-1 min-w-48" aria-label="Titre de la page" :disabled="lectureSeule" @input="modifie" />
          <label class="flex items-center gap-2 text-sm cursor-pointer"><ToggleSwitch v-model="page.verrouillee" :disabled="lectureSeule" @update:model-value="modifie" /> Page imposée</label>
        </div>
        <div class="card p-3" @click="selection = null">
          <BlocEditor :key="page.cle" :blocs="page.blocs" :verrouilles="page.verrouilles" mode-modele :lecture-seule="lectureSeule" @change="modifie" />
        </div>
      </section>
    </div>
  </template>
</template>
