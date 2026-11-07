<script setup>
import { showDanger } from '@helpers/toast';
import { formatDateFr } from '@helpers/date';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import campagneService from '../../requests/campagneService';
import { messageErreur } from '../../requests/traceService';

const router = useRouter();

const campagnes = ref([]);
const options = ref(null);
const chargement = ref(true);
const dialogue = ref(false);
const envoi = ref(false);
const form = reactive({ diplome: null, anneeUniversitaire: null, anneeFormation: null, libelle: '', ouvertureLe: null, depotLimite: null, evaluationLimite: null, autoEvaluationRequise: false });

const charger = async () => {
  try {
    const [{ data: liste }, { data: opt }] = await Promise.all([campagneService.liste(), campagneService.options()]);
    campagnes.value = liste;
    options.value = opt;
  } catch (e) {
    showDanger(messageErreur(e), 'Chargement impossible', 6000);
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const formation = computed(() => options.value?.formations.find((f) => f.id === form.diplome) ?? null);
const anneesDeBut = computed(() => (formation.value?.annees ?? []).filter((a) => a.anneeUniversitaire === form.anneeUniversitaire));

const ouvrir = () => {
  Object.assign(form, { diplome: null, anneeUniversitaire: options.value?.anneeCourante ?? null, anneeFormation: null, libelle: '', ouvertureLe: new Date(), depotLimite: null, evaluationLimite: null, autoEvaluationRequise: false });
  dialogue.value = true;
};

const libelleAuto = () => {
  const annee = options.value?.anneesUniversitaires.find((a) => a.id === form.anneeUniversitaire)?.libelle;
  const but = anneesDeBut.value.find((a) => a.id === form.anneeFormation)?.libelle;
  return [formation.value?.parcours ?? formation.value?.libelle, but, annee].filter(Boolean).join(' — ');
};

const iso = (d) => (d ? d.toISOString() : null);
const creer = async () => {
  envoi.value = true;
  try {
    const { data } = await campagneService.creer({
      ...form,
      libelle: form.libelle.trim() || libelleAuto(),
      ouvertureLe: iso(form.ouvertureLe),
      depotLimite: iso(form.depotLimite),
      evaluationLimite: iso(form.evaluationLimite),
    });
    dialogue.value = false;
    router.push(`/unifolio/admin/campagnes/${data.uuid}`);
  } catch (e) {
    showDanger(messageErreur(e), 'Création impossible', 8000);
  } finally {
    envoi.value = false;
  }
};

const date = (iso) => formatDateFr(iso, 'moyenne', '—');
</script>

<template>
  <HeaderComponent
    icon="pi pi-calendar"
    titre="Campagnes"
    description="Calendrier de dépôt et d'évaluation d'une formation, et affectation des évaluateurs."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Nouvelle campagne" icon="pi pi-plus" :disabled="!options" @click="ouvrir" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="campagnes.length === 0" icon="pi pi-calendar" color="emerald" title="Aucune campagne" description="Créez une campagne pour qu'une formation puisse faire évaluer ses traces." />

  <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <Card bare tag="button"
 v-for="c in campagnes"
 :key="c.uuid"
 type="button"
 class="p-4 m-0 text-left flex flex-col gap-3 hover:shadow-md transition-shadow cursor-pointer"
 @click="router.push(`/unifolio/admin/campagnes/${c.uuid}`)">
      <div class="flex items-start justify-between gap-2">
        <span class="font-semibold">{{ c.libelle }}</span>
        <Tag :value="c.phase.libelle" :severity="c.phase.badge" />
      </div>
      <span class="text-sm text-muted-color">
        {{ [c.diplome.parcours ?? c.diplome.libelle, c.anneeFormation?.libelle, c.anneeUniversitaire.libelle].filter(Boolean).join(' · ') }}
      </span>
      <div class="text-sm grid grid-cols-3 gap-2">
        <div><div class="text-muted-color text-xs">Ouverture</div>{{ date(c.ouvertureLe) }}</div>
        <div><div class="text-muted-color text-xs">Dépôt</div>{{ date(c.depotLimite) }}</div>
        <div><div class="text-muted-color text-xs">Évaluation</div>{{ date(c.evaluationLimite) }}</div>
      </div>
      <div class="flex flex-wrap gap-2 text-xs">
        <Tag :value="`${c.nbAffectes} / ${c.nbEtudiants} étudiants affectés`" :severity="c.nbAffectes === c.nbEtudiants && c.nbEtudiants > 0 ? 'success' : 'warn'" />
        <Tag :value="`${c.nbEvaluations} évaluation(s)`" severity="secondary" />
        <Tag v-if="c.enRetard" value="En retard" severity="danger" />
      </div>
    </Card>
  </div>

  <Dialog v-model:visible="dialogue" header="Nouvelle campagne" modal :style="{ width: '40rem' }" :breakpoints="{ '640px': '95vw' }">
    <div v-if="options" class="flex flex-col gap-4">
      <div class="flex flex-col gap-1">
        <label for="c-formation" class="font-medium">Formation</label>
        <Select id="c-formation" v-model="form.diplome" :options="options.formations" option-label="libelle" option-value="id" filter placeholder="Choisir la formation" @change="form.anneeFormation = null" />
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label for="c-annee" class="font-medium">Année universitaire</label>
          <Select id="c-annee" v-model="form.anneeUniversitaire" :options="options.anneesUniversitaires" option-label="libelle" option-value="id" @change="form.anneeFormation = null" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="c-but" class="font-medium">Année de BUT</label>
          <Select id="c-but" v-model="form.anneeFormation" :options="anneesDeBut" option-label="libelle" option-value="id" show-clear placeholder="Toutes les années" :disabled="!form.diplome" />
        </div>
      </div>
      <div class="flex flex-col gap-1">
        <label for="c-libelle" class="font-medium">Libellé</label>
        <InputText id="c-libelle" v-model="form.libelle" :placeholder="libelleAuto() || 'Généré à partir de la formation'" />
      </div>
      <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1">
          <label for="c-ouv" class="font-medium">Ouverture</label>
          <DatePicker id="c-ouv" v-model="form.ouvertureLe" show-time hour-format="24" date-format="dd/mm/yy" show-icon />
        </div>
        <div class="flex flex-col gap-1">
          <label for="c-depot" class="font-medium">Limite de dépôt</label>
          <DatePicker id="c-depot" v-model="form.depotLimite" show-time hour-format="24" date-format="dd/mm/yy" show-icon show-button-bar />
        </div>
        <div class="flex flex-col gap-1">
          <label for="c-eval" class="font-medium">Limite d'évaluation</label>
          <DatePicker id="c-eval" v-model="form.evaluationLimite" show-time hour-format="24" date-format="dd/mm/yy" show-icon show-button-bar />
        </div>
      </div>
      <label class="flex items-center gap-3 cursor-pointer">
        <ToggleSwitch v-model="form.autoEvaluationRequise" />
        <span>Exiger l'auto-évaluation de l'étudiant avant l'évaluation</span>
      </label>
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="dialogue = false" />
      <Button label="Créer" icon="pi pi-check" :loading="envoi" :disabled="!form.diplome || !form.anneeUniversitaire" @click="creer" />
    </template>
  </Dialog>
</template>
