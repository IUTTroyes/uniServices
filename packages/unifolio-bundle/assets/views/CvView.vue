<script setup>
import { showSuccess } from '@helpers/toast';
import { onMounted, reactive, ref } from 'vue';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ChampTags from '../components/portfolio/ChampTags.vue';
import { useChargement } from '../composables/useChargement';
import { cvService, messagesErreur } from '../requests/portfolioService';

const cv = reactive({ poste: '', description: '', langues: [], softSkills: [], hardSkills: [], reseaux: [], experiences: [], formations: [] });
const enregistrement = ref(false);
const erreurs = ref([]);
const dirty = ref(false);
const misAJourLe = ref(null);

const verser = (d) => {
  Object.assign(cv, {
    poste: d.poste ?? '', description: d.description ?? '', langues: d.langues, softSkills: d.softSkills, hardSkills: d.hardSkills,
    reseaux: d.reseaux, experiences: d.experiences, formations: d.formations,
  });
  misAJourLe.value = d.misAJourLe;
  dirty.value = false;
};

const { chargement, erreur: erreurChargement } = useChargement(() => cvService.lire(), { messageParDefaut: 'Impossible de charger votre CV.', apres: verser });

const modifie = () => { dirty.value = true; };
const ajouter = (liste, modele) => { cv[liste].push({ ...modele }); modifie(); };
const retirer = (liste, i) => { cv[liste].splice(i, 1); modifie(); };
const deplacer = (liste, i, delta) => {
  const j = i + delta;
  if (j < 0 || j >= cv[liste].length) return;
  const [x] = cv[liste].splice(i, 1);
  cv[liste].splice(j, 0, x);
  modifie();
};

const enregistrer = async () => {
  enregistrement.value = true;
  erreurs.value = [];
  try {
    const { data } = await cvService.enregistrer(cv);
    verser(data);
    showSuccess(undefined, 'CV enregistré', 3000);
  } catch (e) {
    erreurs.value = messagesErreur(e);
  } finally {
    enregistrement.value = false;
  }
};

const experienceVide = { poste: '', entreprise: '', dateDebut: '', dateFin: '', description: '' };
const formationVide = { diplome: '', etablissement: '', dateDebut: '', dateFin: '', description: '' };
</script>

<template>
  <HeaderComponent
    icon="pi pi-id-card"
    titre="Mon CV"
    description="Il alimente le bloc « Curriculum vitæ » de vos portfolios."
    :show-back="true"
    back-url="/unifolio/portfolios"
    color="emerald"
  >
    <template #actions>
      <Button label="Enregistrer" icon="pi pi-save" :disabled="!dirty" :loading="enregistrement" @click="enregistrer" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreurChargement" icon="pi pi-exclamation-triangle" color="rose" title="CV indisponible" :description="erreurChargement" />

  <div v-else class="flex flex-col gap-4">
    <Alert icon-native v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
      <ul class="m-0 pl-4"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
    </Alert>

    <Card bare tag="section" class="p-4 flex flex-col gap-3">
      <h3 class="text-base font-semibold m-0">Présentation</h3>
      <div class="flex flex-col gap-1">
        <label for="cv-poste" class="font-medium">Poste ou domaine recherché</label>
        <InputText id="cv-poste" v-model="cv.poste" maxlength="255" @input="modifie" />
      </div>
      <div class="flex flex-col gap-1">
        <label for="cv-desc" class="font-medium">Présentation</label>
        <Textarea id="cv-desc" v-model="cv.description" rows="4" auto-resize maxlength="3000" @input="modifie" />
      </div>
    </Card>

    <Card bare tag="section" class="p-4 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold m-0">Expériences</h3>
        <Button label="Ajouter" icon="pi pi-plus" size="small" outlined @click="ajouter('experiences', experienceVide)" />
      </div>
      <p v-if="!cv.experiences.length" class="m-0 text-sm text-muted-color">Stages, alternance, jobs, projets associatifs…</p>
      <div v-for="(e, i) in cv.experiences" :key="i" class="rounded-lg border border-surface-200 p-3 flex flex-col gap-2">
        <div class="grid gap-2 sm:grid-cols-2">
          <InputText v-model="e.poste" placeholder="Poste *" maxlength="255" :aria-label="`Poste de l'expérience ${i + 1}`" @input="modifie" />
          <InputText v-model="e.entreprise" placeholder="Entreprise" maxlength="255" :aria-label="`Entreprise de l'expérience ${i + 1}`" @input="modifie" />
          <label class="text-sm flex flex-col gap-1">Début <InputText v-model="e.dateDebut" type="date" @input="modifie" /></label>
          <label class="text-sm flex flex-col gap-1">Fin <InputText v-model="e.dateFin" type="date" @input="modifie" /></label>
        </div>
        <Textarea v-model="e.description" rows="2" auto-resize placeholder="Missions, résultats…" maxlength="2000" :aria-label="`Description de l'expérience ${i + 1}`" @input="modifie" />
        <div class="flex justify-end">
          <Button icon="pi pi-arrow-up" size="small" text rounded severity="secondary" :disabled="i === 0" aria-label="Monter" @click="deplacer('experiences', i, -1)" />
          <Button icon="pi pi-arrow-down" size="small" text rounded severity="secondary" :disabled="i === cv.experiences.length - 1" aria-label="Descendre" @click="deplacer('experiences', i, 1)" />
          <Button icon="pi pi-trash" size="small" text rounded severity="danger" aria-label="Supprimer l'expérience" @click="retirer('experiences', i)" />
        </div>
      </div>
    </Card>

    <Card bare tag="section" class="p-4 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold m-0">Formations</h3>
        <Button label="Ajouter" icon="pi pi-plus" size="small" outlined @click="ajouter('formations', formationVide)" />
      </div>
      <div v-for="(f, i) in cv.formations" :key="i" class="rounded-lg border border-surface-200 p-3 flex flex-col gap-2">
        <div class="grid gap-2 sm:grid-cols-2">
          <InputText v-model="f.diplome" placeholder="Diplôme ou formation *" maxlength="255" :aria-label="`Intitulé de la formation ${i + 1}`" @input="modifie" />
          <InputText v-model="f.etablissement" placeholder="Établissement" maxlength="255" :aria-label="`Établissement de la formation ${i + 1}`" @input="modifie" />
          <label class="text-sm flex flex-col gap-1">Début <InputText v-model="f.dateDebut" type="date" @input="modifie" /></label>
          <label class="text-sm flex flex-col gap-1">Fin <InputText v-model="f.dateFin" type="date" @input="modifie" /></label>
        </div>
        <div class="flex justify-end">
          <Button icon="pi pi-arrow-up" size="small" text rounded severity="secondary" :disabled="i === 0" aria-label="Monter" @click="deplacer('formations', i, -1)" />
          <Button icon="pi pi-arrow-down" size="small" text rounded severity="secondary" :disabled="i === cv.formations.length - 1" aria-label="Descendre" @click="deplacer('formations', i, 1)" />
          <Button icon="pi pi-trash" size="small" text rounded severity="danger" aria-label="Supprimer la formation" @click="retirer('formations', i)" />
        </div>
      </div>
    </Card>

    <Card bare tag="section" class="p-4 grid gap-4 md:grid-cols-2">
      <div class="flex flex-col gap-1">
        <label for="cv-hard" class="font-medium">Compétences techniques</label>
        <ChampTags id="cv-hard" v-model="cv.hardSkills" placeholder="PHP, Figma… (Entrée pour ajouter)" @update:model-value="modifie" />
      </div>
      <div class="flex flex-col gap-1">
        <label for="cv-soft" class="font-medium">Savoir-être</label>
        <ChampTags id="cv-soft" v-model="cv.softSkills" placeholder="Autonomie, esprit d'équipe…" @update:model-value="modifie" />
      </div>
    </Card>

    <Card bare tag="section" class="p-4 grid gap-4 md:grid-cols-2">
      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold m-0">Langues</h3>
          <Button label="Ajouter" icon="pi pi-plus" size="small" text @click="ajouter('langues', { langue: '', niveau: '' })" />
        </div>
        <div v-for="(l, i) in cv.langues" :key="i" class="flex gap-1">
          <InputText v-model="l.langue" placeholder="Langue" size="small" class="flex-1" :aria-label="`Langue ${i + 1}`" @input="modifie" />
          <InputText v-model="l.niveau" placeholder="Niveau (B2…)" size="small" class="w-28" :aria-label="`Niveau de la langue ${i + 1}`" @input="modifie" />
          <Button icon="pi pi-times" size="small" text severity="danger" aria-label="Retirer la langue" @click="retirer('langues', i)" />
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold m-0">Liens</h3>
          <Button label="Ajouter" icon="pi pi-plus" size="small" text @click="ajouter('reseaux', { type: 'site', url: '' })" />
        </div>
        <div v-for="(r, i) in cv.reseaux" :key="i" class="flex gap-1">
          <InputText v-model="r.type" placeholder="linkedin, github…" size="small" class="w-32" :aria-label="`Type du lien ${i + 1}`" @input="modifie" />
          <InputText v-model="r.url" placeholder="https://…" size="small" class="flex-1" type="url" :aria-label="`Adresse du lien ${i + 1}`" @input="modifie" />
          <Button icon="pi pi-times" size="small" text severity="danger" aria-label="Retirer le lien" @click="retirer('reseaux', i)" />
        </div>
      </div>
    </Card>
  </div>
</template>
