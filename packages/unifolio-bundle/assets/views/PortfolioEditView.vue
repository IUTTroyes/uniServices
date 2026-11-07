<script setup>
import { showSuccess, showWarn } from '@helpers/toast';
import { computed, onBeforeUnmount, onMounted, provide, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useConfirmation } from '@composables/useConfirmation';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import BlocEditor from '../blocs/BlocEditor.vue';
import BlocRender from '../blocs/BlocRender.vue';
import { copie, incomplets } from '../blocs/schema';
import PartageDialog from '../components/portfolio/PartageDialog.vue';
import ApparenceDialog from '../components/portfolio/ApparenceDialog.vue';
import CopieVitrineDialog from '../components/portfolio/CopieVitrineDialog.vue';
import { portfolioService, messagesErreur } from '../requests/portfolioService';

const route = useRoute();
const { confirmer } = useConfirmation();

const portfolio = ref(null);
const donnees = ref(null);
const chargement = ref(true);
const erreurChargement = ref(null);
const pageUuid = ref(null);
const blocs = reactive([]);
const version = ref(1);
const dirty = ref(false);
const apercu = ref(false);
const enregistrement = ref(false);
const erreurs = ref([]);
const selection = ref(null);
provide('selectionBloc', selection);
const partageOuvert = ref(false);
const copieOuverte = ref(false);
const apparenceOuverte = ref(false);
const majApparence = (detail) => { portfolio.value.apparence = detail.apparence; portfolio.value.logo = detail.logo; };
const dialogue = reactive({ ouvert: false, mode: 'ajout', titre: '', page: null });

const pages = computed(() => portfolio.value?.pages ?? []);
const page = computed(() => pages.value.find((p) => p.uuid === pageUuid.value) ?? null);
const lectureSeule = computed(() => !portfolio.value?.modifiable);
const manques = computed(() => incomplets(blocs));
const conflit = computed(() => erreurs.value.some((e) => e.includes('modifiée ailleurs')));

// ---------- Chargement ----------
const choisirPage = (uuid) => {
  pageUuid.value = uuid;
  const p = pages.value.find((x) => x.uuid === uuid);
  blocs.splice(0, blocs.length, ...copie(p?.blocs ?? []));
  version.value = p?.version ?? 1;
  dirty.value = false;
  selection.value = null;
  erreurs.value = [];
};

const charger = async (garderPage = false) => {
  try {
    const [{ data: p }, { data: d }] = await Promise.all([portfolioService.lire(route.params.uuid), portfolioService.donnees(route.params.uuid)]);
    portfolio.value = p;
    donnees.value = d;
    choisirPage(garderPage && p.pages.some((x) => x.uuid === pageUuid.value) ? pageUuid.value : p.pages[0]?.uuid ?? null);
  } catch (e) {
    erreurChargement.value = e?.response?.status === 403 ? 'Ce portfolio ne vous appartient pas.' : messagesErreur(e, 'Impossible de charger ce portfolio.')[0];
  } finally {
    chargement.value = false;
  }
};

const avantQuitter = (event) => {
  if (dirty.value) {
    event.preventDefault();
    event.returnValue = '';
  }
};
const raccourci = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
    event.preventDefault();
    enregistrer();
  }
};
onMounted(() => {
  charger();
  window.addEventListener('beforeunload', avantQuitter);
  window.addEventListener('keydown', raccourci);
});
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', avantQuitter);
  window.removeEventListener('keydown', raccourci);
});

// ---------- Enregistrement ----------
const enregistrer = async () => {
  if (!page.value || lectureSeule.value || enregistrement.value) return true;
  enregistrement.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.enregistrerBlocs(portfolio.value.uuid, page.value.uuid, blocs, version.value);
    const i = portfolio.value.pages.findIndex((p) => p.uuid === data.uuid);
    portfolio.value.pages[i] = data;
    blocs.splice(0, blocs.length, ...copie(data.blocs));
    version.value = data.version;
    dirty.value = false;
    showSuccess(undefined, 'Page enregistrée', 2500);
    return true;
  } catch (e) {
    erreurs.value = messagesErreur(e);
    return false;
  } finally {
    enregistrement.value = false;
  }
};

const aller = (uuid) => {
  if (uuid === pageUuid.value) return;
  if (!dirty.value) {
    choisirPage(uuid);
    return;
  }
  confirmer({
    header: 'Modifications non enregistrées',
    message: 'Enregistrer cette page avant de la quitter ?',
    icon: 'pi pi-save',
    libelle: 'Enregistrer',
    libelleRefus: 'Ignorer les modifications',
    accept: async () => { if (await enregistrer()) choisirPage(uuid); },
    reject: () => choisirPage(uuid),
  });
};

// ---------- Pages ----------
const ouvrirDialogue = (mode, p = null) => {
  Object.assign(dialogue, { ouvert: true, mode, page: p, titre: mode === 'ajout' ? '' : mode === 'portfolio' ? portfolio.value.titre : p.titre });
};

const validerDialogue = async () => {
  try {
    if (dialogue.mode === 'ajout') {
      const { data } = await portfolioService.ajouterPage(portfolio.value.uuid, dialogue.titre);
      portfolio.value.pages.push(data);
      dialogue.ouvert = false;
      aller(data.uuid);
    } else if (dialogue.mode === 'portfolio') {
      const { data } = await portfolioService.renommer(portfolio.value.uuid, dialogue.titre);
      portfolio.value.titre = data.titre;
      dialogue.ouvert = false;
    } else {
      const { data } = await portfolioService.renommerPage(portfolio.value.uuid, dialogue.page.uuid, dialogue.titre);
      dialogue.page.titre = data.titre;
      dialogue.ouvert = false;
    }
  } catch (e) {
    showWarn(messagesErreur(e).join(' '), 'Action impossible', 6000);
  }
};

const supprimerPage = (p) => confirmer({
  header: 'Supprimer la page',
  message: `Supprimer la page « ${p.titre} » et tout son contenu ?`,
  icon: 'pi pi-trash',
  libelle: 'Supprimer',
  danger: true,
  accept: async () => {
    try {
      await portfolioService.supprimerPage(portfolio.value.uuid, p.uuid);
      portfolio.value.pages = portfolio.value.pages.filter((x) => x.uuid !== p.uuid);
      if (pageUuid.value === p.uuid) choisirPage(portfolio.value.pages[0]?.uuid ?? null);
    } catch (e) {
      showWarn(messagesErreur(e).join(' '), 'Suppression impossible', 6000);
    }
  },
});

const reordonner = async () => {
  try {
    await portfolioService.ordonnerPages(portfolio.value.uuid, portfolio.value.pages.map((p) => p.uuid));
  } catch (e) {
    showWarn(messagesErreur(e).join(' '), 'Ordre non enregistré', 6000);
    charger(true);
  }
};
</script>

<template>
  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreurChargement" icon="pi pi-exclamation-triangle" color="rose" title="Portfolio indisponible" :description="erreurChargement" />

  <template v-else-if="portfolio">
    <HeaderComponent
      icon="pi pi-book"
      :titre="portfolio.titre"
      :description="[portfolio.scolarite?.annee, portfolio.scolarite?.anneeUniversitaire, portfolio.campagne?.libelle].filter(Boolean).join(' · ')"
      :show-back="true"
      back-url="/unifolio/portfolios"
      color="emerald"
    >
      <template #actions>
        <Tag :value="portfolio.type.libelle" :severity="portfolio.type.value === 'academique' ? 'success' : 'info'" />
        <Tag v-if="lectureSeule" value="Lecture seule" severity="warn" icon="pi pi-lock" />
        <Button v-if="!lectureSeule" icon="pi pi-pencil" severity="secondary" text rounded aria-label="Renommer le portfolio" @click="ouvrirDialogue('portfolio')" />
        <Button v-if="portfolio.type.value === 'academique'" label="Copier dans ma vitrine" icon="pi pi-copy" severity="secondary" outlined @click="copieOuverte = true" />
        <Button v-if="!lectureSeule" label="Apparence" icon="pi pi-palette" severity="secondary" outlined @click="apparenceOuverte = true" />
        <Button label="Partager" icon="pi pi-share-alt" @click="partageOuvert = true" />
      </template>
    </HeaderComponent>

    <Alert icon-native v-if="lectureSeule" severity="info" :closable="false">La campagne de cette année est clôturée : votre portfolio est conservé en lecture seule.</Alert>

    <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)] items-start">
      <!-- Pages -->
      <Card bare tag="aside" class="p-3 flex flex-col gap-2 lg:sticky lg:top-2" aria-label="Pages du portfolio">
        <h3 class="text-sm font-semibold m-0">Pages</h3>
        <draggable :list="portfolio.pages" handle=".page-poignee" :disabled="lectureSeule" class="flex flex-col gap-1" @end="reordonner">
          <div
            v-for="p in portfolio.pages"
            :key="p.uuid"
            class="flex items-center gap-1 rounded-lg px-1"
            :class="p.uuid === pageUuid ? 'bg-emerald-50 dark:bg-emerald-950/30' : ''"
          >
            <i v-if="!lectureSeule" class="pi pi-bars page-poignee cursor-move text-muted-color p-1" aria-hidden="true" />
            <button type="button" class="flex-1 text-left bg-transparent border-0 py-2 cursor-pointer text-sm truncate" :class="p.uuid === pageUuid ? 'font-semibold' : ''" :aria-current="p.uuid === pageUuid ? 'page' : undefined" @click="aller(p.uuid)">
              {{ p.titre }}
            </button>
            <i v-if="p.verrouillee" class="pi pi-lock text-amber-600 text-xs" title="Page imposée par le modèle" />
            <template v-else-if="!lectureSeule">
              <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" :aria-label="`Renommer ${p.titre}`" @click="ouvrirDialogue('renommer', p)" />
              <Button icon="pi pi-trash" size="small" text rounded severity="danger" :aria-label="`Supprimer ${p.titre}`" @click="supprimerPage(p)" />
            </template>
          </div>
        </draggable>
        <Button v-if="!lectureSeule" label="Ajouter une page" icon="pi pi-plus" size="small" outlined @click="ouvrirDialogue('ajout')" />
      </Card>

      <!-- Page courante -->
      <section v-if="page" class="flex flex-col gap-3 min-w-0">
        <Card bare class="p-3 flex flex-wrap items-center gap-2 sticky top-2 z-10 shadow-sm">
          <h2 class="m-0 text-lg font-semibold flex-1 min-w-40 truncate">{{ page.titre }}</h2>
          <Tag v-if="dirty" value="Modifications non enregistrées" severity="warn" icon="pi pi-pencil" />
          <Button :label="apercu ? 'Modifier' : 'Aperçu'" :icon="apercu ? 'pi pi-pencil' : 'pi pi-eye'" severity="secondary" outlined size="small" @click="apercu = !apercu; selection = null" />
          <Button v-if="!lectureSeule" label="Enregistrer" icon="pi pi-save" size="small" :disabled="!dirty" :loading="enregistrement" @click="enregistrer" />
        </Card>

        <Alert icon-native v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
          <ul class="m-0 pl-4 flex flex-col gap-1"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
          <Button v-if="conflit" label="Recharger la page" icon="pi pi-refresh" size="small" severity="secondary" class="mt-2" @click="charger(true)" />
        </Alert>
        <Alert icon-native v-if="manques.length && !apercu" severity="warn" :closable="false">
          <strong>{{ manques.length }} élément(s) à compléter</strong> (ils n'empêchent pas d'enregistrer, mais bloqueront la publication) :
          <ul class="m-0 mt-1 pl-4"><li v-for="m in manques.slice(0, 5)" :key="m">{{ m }}</li></ul>
        </Alert>

        <Card bare tag="article" v-if="apercu" class="p-6 flex flex-col gap-4" aria-label="Aperçu de la page">
          <BlocRender v-for="b in blocs" :key="b.id" :bloc="b" :donnees="donnees" />
          <p v-if="!blocs.length" class="m-0 text-muted-color">Page vide.</p>
        </Card>
        <Card bare v-else class="p-3" @click="selection = null">
          <BlocEditor :blocs="blocs" :donnees="donnees" :verrouilles="page.verrouilles" :lecture-seule="lectureSeule" @change="dirty = true" />
        </Card>
      </section>
    </div>

    <PartageDialog v-model:visible="partageOuvert" :portfolio="portfolio" :modifs-en-attente="dirty" />
    <ApparenceDialog v-model:visible="apparenceOuverte" :portfolio="portfolio" @change="majApparence" />
    <CopieVitrineDialog v-if="portfolio.type.value === 'academique'" v-model:visible="copieOuverte" :portfolio="portfolio" />

    <Dialog v-model:visible="dialogue.ouvert" :header="dialogue.mode === 'ajout' ? 'Nouvelle page' : dialogue.mode === 'portfolio' ? 'Renommer le portfolio' : 'Renommer la page'" modal :style="{ width: '26rem' }">
      <div class="flex flex-col gap-2">
        <label for="dlg-titre" class="font-medium">Titre</label>
        <InputText id="dlg-titre" v-model="dialogue.titre" maxlength="255" autofocus @keydown.enter.prevent="validerDialogue" />
      </div>
      <template #footer>
        <Button label="Annuler" severity="secondary" outlined @click="dialogue.ouvert = false" />
        <Button label="Valider" icon="pi pi-check" :disabled="!dialogue.titre.trim()" @click="validerDialogue" />
      </template>
    </Dialog>
  </template>
</template>
