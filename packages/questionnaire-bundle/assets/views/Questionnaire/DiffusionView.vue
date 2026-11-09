<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, Kpi, ListSkeleton } from '@components';
import { getPersonnelsService } from '@requests';
import { useConfirmation } from '@composables/useConfirmation';
import { formatDate } from '@/utils/date';
import {
  getDiffusionService,
  getQuestionnaireService,
  getRetoursService,
  preremplirDiffusionService,
  publierResultatsService,
  saveDiffusionService,
} from '@/requests/questionnaire_service';

/**
 * Diffusion ciblée des résultats (équipe) : pour chaque section publiée, les collègues destinataires, pré-remplis
 * d'après le prévisionnel ou l'emploi du temps et modifiables ; validation, puis publication (manuelle, ou automatique
 * à la fin de la relecture selon les réglages du département). Les retours des enseignants s'affichent ici.
 */
const route = useRoute();
const uuid = route.params.id as string;
const { confirmer } = useConfirmation();

const chargement = ref(true);
const questionnaire = ref<any>(null);
const etat = ref<any>(null);
const choix = ref<Record<number, number[]>>({});
const personnels = ref<any[]>([]);
const retours = ref<any[]>([]);
const action = ref<string | null>(null);

const ORIGINES: Record<string, { libelle: string; severite: string }> = {
  previsionnel: { libelle: 'Prévisionnel', severite: 'info' },
  edt: { libelle: 'Emploi du temps', severite: 'secondary' },
  intervenant: { libelle: 'Intervenant évalué', severite: 'success' },
  manuelle: { libelle: 'Ajout manuel', severite: 'warn' },
};
const ETAPES: Record<string, { libelle: string; severite: string }> = {
  collecte: { libelle: 'Collecte en cours', severite: 'info' },
  relecture: { libelle: 'En relecture', severite: 'warn' },
  publies: { libelle: 'Résultats publiés', severite: 'success' },
};

const options = computed(() => {
  const liste = personnels.value.map((p: any) => ({ label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display, value: p.id }));
  // Garder visibles les destinataires extérieurs au département (intervenants mutualisés, vacataires…).
  for (const s of etat.value?.sections ?? []) {
    for (const d of s.destinataires) {
      if (!liste.some(o => o.value === d.id)) liste.push({ label: d.nom, value: d.id });
    }
  }
  return liste.sort((a, b) => a.label.localeCompare(b.label));
});
const sansDestinataire = computed(() => (etat.value?.sections ?? []).filter((s: any) => (choix.value[s.id] ?? []).length === 0).length);
const retoursParSection = computed(() => {
  const m: Record<number, any[]> = {};
  for (const r of retours.value) (m[r.section] ??= []).push(r);
  return m;
});

function appliquer(nouvelEtat: any) {
  etat.value = nouvelEtat;
  choix.value = Object.fromEntries((nouvelEtat?.sections ?? []).map((s: any) => [s.id, s.destinataires.map((d: any) => d.id)]));
}

async function charger() {
  chargement.value = true;
  try {
    questionnaire.value = await getQuestionnaireService(uuid);
    appliquer(await getDiffusionService(uuid));
    const deptId = questionnaire.value?.departement?.id;
    const [liste, r] = await Promise.all([
      deptId ? getPersonnelsService({ departement: deptId, pagination: false }).catch(() => []) : Promise.resolve([]),
      getRetoursService(uuid).catch(() => []),
    ]);
    personnels.value = liste?.member || liste || [];
    retours.value = r || [];
  } finally {
    chargement.value = false;
  }
}

async function executer(nom: string, fn: () => Promise<any>) {
  action.value = nom;
  try {
    appliquer(await fn());
  } catch (e) {
    // apiCall a affiché l'erreur du serveur.
  } finally {
    action.value = null;
  }
}

const enregistrer = (valider: boolean) => executer(valider ? 'valider' : 'enregistrer', () => saveDiffusionService(uuid, choix.value, valider));
const preremplir = () => executer('preremplir', () => preremplirDiffusionService(uuid));

async function publier() {
  const ok = await confirmer({
    titre: 'Publier les résultats',
    message: 'Chaque destinataire sera prévenu et pourra consulter les résultats des sections qui le concernent. Continuer ?',
    libelle: 'Publier',
  });
  if (ok) await executer('publier', () => publierResultatsService(uuid));
}

onMounted(charger);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      :titre="`Diffusion des résultats — ${questionnaire?.title ?? ''}`"
      description="Choisissez les collègues qui recevront les résultats de chaque section. La liste est pré-remplie d'après le prévisionnel ou l'emploi du temps : vérifiez-la, puis validez-la."
      icon="pi pi-share-alt"
      color="green"
      :back-url="{ name: 'questionnaire_responses', params: { id: uuid } }"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <ActionButtonVertical icon="pi pi-refresh" label="Suggestions" severity="secondary" :disabled="!!action || etat?.etape === 'collecte' && !etat?.sections?.length" @click="preremplir" />
          <ActionButtonVertical icon="pi pi-check" label="Valider" severity="primary" :disabled="!!action" @click="enregistrer(true)" />
          <ActionButtonVertical
            icon="pi pi-send"
            label="Publier"
            severity="success"
            :disabled="!!action || etat?.etape !== 'relecture' || !etat?.validee"
            @click="publier"
          />
        </div>
      </template>
    </HeaderComponent>

    <ListSkeleton v-if="chargement" />

    <template v-else-if="etat">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Kpi label="Étape" :value="ETAPES[etat.etape]?.libelle ?? etat.etape" icon="pi pi-flag" color="green" />
        <Kpi label="Diffusion" :value="etat.validee ? 'Validée' : 'À valider'" :description="etat.validee ? `le ${formatDate(etat.validee)}${etat.valideePar ? ' par ' + etat.valideePar : ''}` : ''" icon="pi pi-check-square" :color="etat.validee ? 'emerald' : 'amber'" />
        <Kpi label="Publication automatique" :value="etat.publieLe ? formatDate(etat.publieLe) : (etat.finRelecture ? formatDate(etat.finRelecture) : '—')" :description="etat.publieLe ? 'Résultats publiés' : (etat.finRelecture ? 'Fin de la relecture' : 'Manuelle ou questionnaire non clôturé')" icon="pi pi-calendar" color="blue" />
        <Kpi label="Sections sans destinataire" :value="sansDestinataire" icon="pi pi-exclamation-circle" :color="sansDestinataire ? 'amber' : 'emerald'" />
      </div>

      <EmptyState v-if="etat.sections.length === 0" icon="pi pi-inbox" title="Aucune section publiée" description="Publiez le questionnaire pour préparer la diffusion de ses résultats." />

      <Card v-else title="Destinataires par section" icon="pi pi-users" color="green">
        <ul class="space-y-5">
          <li v-for="section in etat.sections" :key="section.id" class="space-y-2 pb-4 border-b border-slate-100 dark:border-slate-800 last:border-0">
            <div class="flex flex-wrap items-center gap-2">
              <span :id="`titre-${section.id}`" class="font-semibold text-slate-900 dark:text-white">{{ section.titre }}</span>
              <Tag v-if="section.enseignement" severity="secondary" :value="section.enseignement.code || section.enseignement.libelle" />
              <Tag v-if="section.intervenant" severity="info" :value="`Intervenant : ${section.intervenant}`" />
            </div>
            <MultiSelect
              v-model="choix[section.id]"
              :options="options"
              option-label="label"
              option-value="value"
              filter
              display="chip"
              placeholder="Aucun destinataire"
              class="w-full"
              :aria-labelledby="`titre-${section.id}`"
            />
            <div v-if="section.destinataires.length" class="flex flex-wrap gap-2">
              <Tag
                v-for="d in section.destinataires"
                :key="d.id"
                :severity="ORIGINES[d.origine]?.severite ?? 'secondary'"
                :value="`${d.nom} · ${ORIGINES[d.origine]?.libelle ?? d.origine}${d.notifie ? ' · prévenu' : ''}`"
              />
            </div>
            <div v-for="r in retoursParSection[section.id] ?? []" :key="r.personnel" class="q-callout q-callout-info text-sm">
              <div>
                <p class="font-semibold">Retour de {{ r.personnel }}</p>
                <p v-if="r.commentaire" class="whitespace-pre-line">{{ r.commentaire }}</p>
                <p v-if="r.planAction" class="whitespace-pre-line"><strong>Plan d'action :</strong> {{ r.planAction }}</p>
              </div>
            </div>
          </li>
        </ul>
        <div class="q-actions">
          <Button label="Enregistrer" severity="secondary" outlined :loading="action === 'enregistrer'" @click="enregistrer(false)" />
          <Button label="Enregistrer et valider" icon="pi pi-check" :loading="action === 'valider'" @click="enregistrer(true)" />
        </div>
      </Card>
    </template>
  </div>
</template>
