<script setup>
import { showDanger, showSuccess } from '@helpers/toast';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirmation } from '@composables/useConfirmation';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import campagneService from '../../requests/campagneService';
import AffectationsPanel from '../../components/campagne/AffectationsPanel.vue';
import { modeleService } from '../../requests/portfolioService';
import { messageErreur } from '../../requests/traceService';

const route = useRoute();
const router = useRouter();
const { confirmer } = useConfirmation();

const campagne = ref(null);
const chargement = ref(true);
const occupe = ref(false);
const form = reactive({ libelle: '', ouvertureLe: null, depotLimite: null, evaluationLimite: null, clotureLe: null, autoEvaluationRequise: false, modele: null });
const modeles = ref([]);

const verser = (c) => {
  campagne.value = c;
  const d = (iso) => (iso ? new Date(iso) : null);
  Object.assign(form, { libelle: c.libelle, ouvertureLe: d(c.ouvertureLe), depotLimite: d(c.depotLimite), evaluationLimite: d(c.evaluationLimite), clotureLe: d(c.clotureLe), autoEvaluationRequise: c.autoEvaluationRequise, modele: c.modele?.uuid ?? null });
};

onMounted(async () => {
  try {
    const { data } = await campagneService.lire(route.params.uuid);
    verser(data);
    modeleService.liste().then(({ data: liste }) => { modeles.value = liste.filter((m) => m.actif); }).catch(() => {});
  } catch (e) {
    showDanger(messageErreur(e), 'Campagne introuvable', 6000);
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
      modele: form.modele,
    });
    verser(data);
    showSuccess(undefined, 'Campagne enregistrée', 3000);
  } catch (e) {
    showDanger(messageErreur(e), 'Enregistrement impossible', 8000);
  } finally {
    occupe.value = false;
  }
};

const cloturer = () => confirmer({
  header: 'Clôturer la campagne',
  message: 'Clôturer la campagne ? Les évaluations ne seront plus modifiables et plus aucun dépôt ne sera accepté.',
  icon: 'pi pi-lock',
  libelle: 'Clôturer',
  danger: true,
  accept: async () => {
    try {
      const { data } = await campagneService.cloturer(campagne.value.uuid);
      verser(data);
      showSuccess(undefined, 'Campagne clôturée', 3000);
    } catch (e) {
      showDanger(messageErreur(e), 'Clôture impossible', 6000);
    }
  },
});

const supprimer = () => confirmer({
  header: 'Supprimer la campagne',
  message: 'Supprimer cette campagne et ses affectations ?',
  icon: 'pi pi-trash',
  libelle: 'Supprimer',
  danger: true,
  accept: async () => {
    try {
      await campagneService.supprimer(campagne.value.uuid);
      router.push('/unifolio/admin/campagnes');
    } catch (e) {
      showDanger(messageErreur(e), 'Suppression impossible', 7000);
    }
  },
});

// Les compteurs d'affectation de la campagne se mettent à jour après une affectation ou un retrait.
const rafraichir = async () => { campagne.value = (await campagneService.lire(route.params.uuid)).data; };
</script>

<template>
  <LoadingBar v-if="chargement" />
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
      <Card bare tag="section" class="p-4 flex flex-col gap-4 xl:col-span-1">
        <h3 class="text-base font-semibold m-0">Calendrier</h3>
        <Alert icon-native :closable="false" v-if="cloturee" severity="info">Campagne clôturée : plus aucune modification possible.</Alert>
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
        <div class="flex flex-col gap-1">
          <label for="e-modele" class="font-medium">Modèle de portfolio</label>
          <Select id="e-modele" v-model="form.modele" :options="modeles" option-label="libelle" option-value="uuid" show-clear placeholder="Structure par défaut (une page par compétence)" :disabled="cloturee" />
          <small class="text-muted-color">Appliqué aux portfolios créés ensuite ; les portfolios existants ne changent pas.</small>
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
      </Card>

      <AffectationsPanel :campagne="campagne" :cloturee="cloturee" @change="rafraichir" />
    </div>
  </template>
</template>
