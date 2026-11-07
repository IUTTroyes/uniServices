<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import campagneService from '../../requests/campagneService';
import { messageErreur } from '../../requests/traceService';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const campagne = ref(null);
const chargement = ref(true);
const occupe = ref(false);
const form = reactive({ libelle: '', ouvertureLe: null, depotLimite: null, evaluationLimite: null, clotureLe: null, autoEvaluationRequise: false });

const affectations = ref(null);
const evaluateur = ref(null);
const portee = ref('tous');
const groupe = ref(null);
const cochees = ref([]);
const filtre = ref('');

const verser = (c) => {
  campagne.value = c;
  const d = (iso) => (iso ? new Date(iso) : null);
  Object.assign(form, { libelle: c.libelle, ouvertureLe: d(c.ouvertureLe), depotLimite: d(c.depotLimite), evaluationLimite: d(c.evaluationLimite), clotureLe: d(c.clotureLe), autoEvaluationRequise: c.autoEvaluationRequise });
};

const chargerAffectations = async () => {
  const { data } = await campagneService.affectations(route.params.uuid);
  affectations.value = data;
};

onMounted(async () => {
  try {
    const { data } = await campagneService.lire(route.params.uuid);
    verser(data);
    await chargerAffectations();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Campagne introuvable', detail: messageErreur(e), life: 6000 });
  } finally {
    chargement.value = false;
  }
});

const cloturee = computed(() => campagne.value?.phase.value === 'cloturee');
const iso = (d) => (d ? d.toISOString() : null);

const enregistrer = async () => {
  occupe.value = true;
  try {
    const { data } = await campagneService.modifier(campagne.value.uuid, {
      libelle: form.libelle,
      ouvertureLe: iso(form.ouvertureLe),
      depotLimite: iso(form.depotLimite),
      evaluationLimite: iso(form.evaluationLimite),
      clotureLe: iso(form.clotureLe),
      autoEvaluationRequise: form.autoEvaluationRequise,
    });
    verser(data);
    toast.add({ severity: 'success', summary: 'Campagne enregistrée', life: 3000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreur(e), life: 8000 });
  } finally {
    occupe.value = false;
  }
};

const cloturer = () => confirm.require({
  message: 'Clôturer la campagne ? Les évaluations ne seront plus modifiables et plus aucun dépôt ne sera accepté.',
  header: 'Clôturer la campagne',
  icon: 'pi pi-lock',
  acceptProps: { label: 'Clôturer', severity: 'danger' },
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  accept: async () => {
    try {
      const { data } = await campagneService.cloturer(campagne.value.uuid);
      verser(data);
      toast.add({ severity: 'success', summary: 'Campagne clôturée', life: 3000 });
    } catch (e) {
      toast.add({ severity: 'error', summary: 'Clôture impossible', detail: messageErreur(e), life: 6000 });
    }
  },
});

const supprimer = () => confirm.require({
  message: 'Supprimer cette campagne et ses affectations ?',
  header: 'Supprimer la campagne',
  icon: 'pi pi-trash',
  acceptProps: { label: 'Supprimer', severity: 'danger' },
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  accept: async () => {
    try {
      await campagneService.supprimer(campagne.value.uuid);
      router.push('/unifolio/admin/campagnes');
    } catch (e) {
      toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreur(e), life: 7000 });
    }
  },
});

// ---------- Affectations ----------
const etudiants = computed(() => {
  const q = filtre.value.trim().toLowerCase();
  return (affectations.value?.etudiants ?? []).filter((e) => !q || `${e.nom} ${e.prenom}`.toLowerCase().includes(q));
});
const nomEvaluateur = (id) => affectations.value?.candidats.find((c) => c.id === id)?.nom ?? affectations.value?.evaluateurs.find((c) => c.id === id)?.nom ?? `#${id}`;
const sansEvaluateur = computed(() => (affectations.value?.etudiants ?? []).filter((e) => e.evaluateurs.length === 0).length);

const peutAgir = computed(() => evaluateur.value && !cloturee.value && (portee.value === 'tous' || (portee.value === 'groupe' && groupe.value) || (portee.value === 'selection' && cochees.value.length)));
const donneesAction = () => ({
  evaluateur: evaluateur.value,
  ...(portee.value === 'groupe' ? { groupe: groupe.value } : {}),
  ...(portee.value === 'selection' ? { etudiants: cochees.value } : {}),
});

const affecter = async () => {
  occupe.value = true;
  try {
    const { data } = await campagneService.affecter(campagne.value.uuid, donneesAction());
    toast.add({ severity: 'success', summary: `${data.crees} affectation(s) créée(s)`, life: 3000 });
    cochees.value = [];
    await Promise.all([chargerAffectations(), campagneService.lire(campagne.value.uuid).then(({ data: c }) => (campagne.value = c))]);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Affectation impossible', detail: messageErreur(e), life: 8000 });
  } finally {
    occupe.value = false;
  }
};

const retirer = async () => {
  occupe.value = true;
  try {
    const { data } = await campagneService.desaffecter(campagne.value.uuid, {
      evaluateur: evaluateur.value,
      ...(portee.value === 'selection' ? { etudiants: cochees.value } : {}),
    });
    toast.add({ severity: 'success', summary: `${data.retirees} affectation(s) retirée(s)`, life: 3000 });
    cochees.value = [];
    await Promise.all([chargerAffectations(), campagneService.lire(campagne.value.uuid).then(({ data: c }) => (campagne.value = c))]);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Retrait impossible', detail: messageErreur(e), life: 8000 });
  } finally {
    occupe.value = false;
  }
};

const toutCocher = (coche) => { cochees.value = coche ? etudiants.value.map((e) => e.id) : []; };
</script>

<template>
  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="!campagne" icon="pi pi-exclamation-triangle" color="rose" title="Campagne introuvable" />

  <template v-else>
    <HeaderComponent
      icon="pi pi-calendar"
      :titre="campagne.libelle"
      :description="[campagne.diplome.parcours ?? campagne.diplome.libelle, campagne.anneeFormation?.libelle, campagne.anneeUniversitaire.libelle].filter(Boolean).join(' · ')"
      :show-back="true"
      back-url="/unifolio/admin/campagnes"
      color="emerald"
    >
      <template #actions>
        <Tag :value="campagne.phase.libelle" :severity="campagne.phase.badge" />
        <Tag v-if="campagne.enRetard" value="Évaluation en retard" severity="danger" />
      </template>
    </HeaderComponent>

    <div class="grid gap-4 xl:grid-cols-3">
      <section class="card p-4 flex flex-col gap-4 xl:col-span-1">
        <h3 class="text-base font-semibold m-0">Calendrier</h3>
        <Message v-if="cloturee" severity="info">Campagne clôturée : plus aucune modification possible.</Message>
        <div class="flex flex-col gap-1">
          <label for="e-libelle" class="font-medium">Libellé</label>
          <InputText id="e-libelle" v-model="form.libelle" :disabled="cloturee" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="e-ouv" class="font-medium">Ouverture</label>
          <DatePicker id="e-ouv" v-model="form.ouvertureLe" show-time hour-format="24" date-format="dd/mm/yy" show-icon :disabled="cloturee" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="e-depot" class="font-medium">Limite de dépôt</label>
          <DatePicker id="e-depot" v-model="form.depotLimite" show-time hour-format="24" date-format="dd/mm/yy" show-icon show-button-bar :disabled="cloturee" />
          <small class="text-muted-color">Ensuite, seules les traces dont une révision est demandée peuvent être redéposées.</small>
        </div>
        <div class="flex flex-col gap-1">
          <label for="e-eval" class="font-medium">Limite d'évaluation</label>
          <DatePicker id="e-eval" v-model="form.evaluationLimite" show-time hour-format="24" date-format="dd/mm/yy" show-icon show-button-bar :disabled="cloturee" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="e-clot" class="font-medium">Clôture</label>
          <DatePicker id="e-clot" v-model="form.clotureLe" show-time hour-format="24" date-format="dd/mm/yy" show-icon show-button-bar :disabled="cloturee" />
          <small class="text-muted-color">À la clôture, les évaluations sont figées et le dépôt est fermé.</small>
        </div>
        <label class="flex items-center gap-3 cursor-pointer">
          <ToggleSwitch v-model="form.autoEvaluationRequise" :disabled="cloturee" />
          <span>Exiger l'auto-évaluation avant l'évaluation</span>
        </label>
        <div v-if="!cloturee" class="flex flex-wrap gap-2">
          <Button label="Enregistrer" icon="pi pi-save" :loading="occupe" @click="enregistrer" />
          <Button label="Clôturer maintenant" icon="pi pi-lock" severity="warn" outlined @click="cloturer" />
          <Button v-if="campagne.nbEvaluations === 0" icon="pi pi-trash" severity="danger" text aria-label="Supprimer la campagne" @click="supprimer" />
        </div>
      </section>

      <section class="card p-4 flex flex-col gap-4 xl:col-span-2">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="text-base font-semibold m-0 flex-1">Affectation des évaluateurs</h3>
          <Tag :value="`${campagne.nbAffectes} / ${campagne.nbEtudiants} étudiants affectés`" :severity="sansEvaluateur === 0 && campagne.nbEtudiants > 0 ? 'success' : 'warn'" />
        </div>

        <div v-if="affectations" class="rounded-lg border border-surface-200 p-3 flex flex-col gap-3">
          <div class="grid gap-3 md:grid-cols-2">
            <div class="flex flex-col gap-1">
              <label for="a-eval" class="text-sm font-medium">Enseignant</label>
              <Select id="a-eval" v-model="evaluateur" :options="affectations.candidats" option-label="nom" option-value="id" filter placeholder="Choisir un enseignant" :disabled="cloturee" />
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium">Étudiants concernés</span>
              <SelectButton
                v-model="portee"
                :options="[{ v: 'tous', l: 'Tous' }, { v: 'groupe', l: 'Un groupe' }, { v: 'selection', l: 'Sélection' }]"
                option-label="l"
                option-value="v"
                :allow-empty="false"
                :disabled="cloturee"
              />
            </div>
          </div>
          <Select
            v-if="portee === 'groupe'"
            v-model="groupe"
            :options="affectations.groupes"
            :option-label="(g) => `${g.libelle} (${g.nbEtudiants})`"
            option-value="id"
            placeholder="Choisir le groupe"
          />
          <p v-if="portee === 'selection'" class="text-sm text-muted-color m-0">Cochez les étudiants dans la liste ci‑dessous ({{ cochees.length }} sélectionné(s)).</p>
          <div class="flex flex-wrap gap-2">
            <Button label="Affecter" icon="pi pi-user-plus" size="small" :disabled="!peutAgir || portee === 'groupe' && !groupe" :loading="occupe" @click="affecter" />
            <Button label="Retirer" icon="pi pi-user-minus" size="small" severity="secondary" outlined :disabled="!evaluateur || cloturee || portee === 'groupe'" :loading="occupe" @click="retirer" />
          </div>
        </div>

        <EmptyState v-if="affectations && affectations.etudiants.length === 0" icon="pi pi-users" color="amber" title="Aucun étudiant" description="Aucun étudiant n'est inscrit dans cette formation pour l'année choisie." compact />

        <div v-else-if="affectations" class="flex flex-col gap-2">
          <InputText v-model="filtre" placeholder="Filtrer les étudiants" aria-label="Filtrer les étudiants" />
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
              <thead>
                <tr class="text-muted-color text-xs border-b border-surface-200">
                  <th class="p-2 w-10"><Checkbox binary :model-value="cochees.length > 0 && cochees.length === etudiants.length" aria-label="Tout cocher" @update:model-value="toutCocher" /></th>
                  <th class="font-medium p-2">Étudiant</th>
                  <th class="font-medium p-2">Année</th>
                  <th class="font-medium p-2">Groupes</th>
                  <th class="font-medium p-2">Évaluateurs</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="e in etudiants" :key="e.id" class="border-b border-surface-100">
                  <td class="p-2"><Checkbox v-model="cochees" :value="e.id" :aria-label="`Sélectionner ${e.nom} ${e.prenom}`" /></td>
                  <td class="p-2 font-medium">{{ e.nom }} {{ e.prenom }}</td>
                  <td class="p-2 text-muted-color">{{ e.annee }}</td>
                  <td class="p-2 text-muted-color">{{ e.groupes.join(', ') }}</td>
                  <td class="p-2">
                    <span v-if="e.evaluateurs.length === 0" class="text-amber-700">—</span>
                    <Tag v-for="id in e.evaluateurs" :key="id" :value="nomEvaluateur(id)" severity="secondary" class="mr-1" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </template>
</template>
