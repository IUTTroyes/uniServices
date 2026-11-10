<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import { Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import { showDanger } from '@helpers/toast.js';
import ResultatsSection from '@/components/Resultats/ResultatsSection.vue';
import { exportExcelService, exportPdfService, getAnalyticsService, getRetoursService, saveRetourService } from '@/requests/questionnaire_service';

/**
 * Restitution web des résultats pour un collègue destinataire : seulement les sections qui le concernent (le serveur
 * filtre), en tableaux accessibles, avec export PDF / Excel par section et un retour (commentaire, plan d'action).
 * L'équipe qui gère le questionnaire voit toutes les sections.
 */
const route = useRoute();
const uuid = route.params.id as string;

const chargement = ref(true);
const resultats = ref<any>(null);
const retours = ref<Record<number, { commentaire: string; planAction: string }>>({});
const enregistrement = ref<number | null>(null);
const exportEnCours = ref<string | null>(null);

const sections = computed(() => resultats.value?.sections ?? []);

async function charger() {
  chargement.value = true;
  try {
    resultats.value = await getAnalyticsService(uuid);
    const liste = await getRetoursService(uuid);
    for (const s of sections.value) {
      const r = (liste || []).find((x: any) => x.section === s.sectionInstanceId);
      retours.value[s.sectionInstanceId] = { commentaire: r?.commentaire ?? '', planAction: r?.planAction ?? '' };
    }
  } catch (e) {
    resultats.value = null;
  } finally {
    chargement.value = false;
  }
}

async function enregistrerRetour(sectionId: number) {
  enregistrement.value = sectionId;
  try {
    const r = retours.value[sectionId];
    await saveRetourService(uuid, sectionId, r.commentaire, r.planAction);
  } finally {
    enregistrement.value = null;
  }
}

async function exporter(format: 'pdf' | 'excel', sectionId: number) {
  exportEnCours.value = `${format}-${sectionId}`;
  try {
    if (format === 'pdf') {
      await exportPdfService(uuid, {}, [sectionId]);
    } else {
      await exportExcelService(uuid, [sectionId]);
    }
  } catch (e: any) {
    showDanger('L\'export n\'a pas pu être généré.');
  } finally {
    exportEnCours.value = null;
  }
}

onMounted(charger);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      :titre="resultats?.title ? `Résultats — ${resultats.title}` : 'Mes résultats'"
      description="Résultats des enseignements qui vous concernent. Vous pouvez y répondre par un commentaire ou un plan d'action, lus par l'équipe qui pilote le questionnaire."
      icon="pi pi-chart-bar"
      color="green"
    />

    <ListSkeleton v-if="chargement" />

    <EmptyState
      v-else-if="!resultats"
      icon="pi pi-lock"
      title="Résultats indisponibles"
      description="Ces résultats ne sont pas (encore) publiés pour vous, ou vous n'en êtes pas destinataire."
    />

    <EmptyState v-else-if="sections.length === 0" icon="pi pi-inbox" title="Aucune section ne vous concerne" />

    <Card
      v-for="section in sections"
      v-else
      :key="section.sectionInstanceId"
      :title="section.sectionTitle"
      :subtitle="section.enseignement || ''"
      icon="pi pi-list"
      color="green"
    >
      <template #header-actions>
        <div class="flex items-center gap-2">
          <Button
            label="PDF"
            icon="pi pi-file-pdf"
            size="small"
            severity="secondary"
            outlined
            :aria-label="`Télécharger le PDF de ${section.sectionTitle}`"
            :disabled="section.masquee"
            :loading="exportEnCours === `pdf-${section.sectionInstanceId}`"
            @click="exporter('pdf', section.sectionInstanceId)"
          />
          <Button
            label="Excel"
            icon="pi pi-file-excel"
            size="small"
            severity="secondary"
            outlined
            :aria-label="`Télécharger le fichier Excel de ${section.sectionTitle}`"
            :disabled="section.masquee"
            :loading="exportEnCours === `excel-${section.sectionInstanceId}`"
            @click="exporter('excel', section.sectionInstanceId)"
          />
        </div>
      </template>

      <ResultatsSection :section="section" :seuil="resultats.seuilMinReponses" />

      <form
        v-if="!resultats.gestion && retours[section.sectionInstanceId]"
        class="q-form mt-6 pt-4 border-t border-slate-200 dark:border-slate-700"
        @submit.prevent="enregistrerRetour(section.sectionInstanceId)"
      >
        <h4 class="text-base font-medium">Mon retour</h4>
        <label class="q-label" :for="`commentaire-${section.sectionInstanceId}`">Commentaire</label>
        <textarea :id="`commentaire-${section.sectionInstanceId}`" v-model="retours[section.sectionInstanceId].commentaire" rows="3" maxlength="10000" class="q-input" />
        <label class="q-label" :for="`plan-${section.sectionInstanceId}`">Plan d'action</label>
        <textarea :id="`plan-${section.sectionInstanceId}`" v-model="retours[section.sectionInstanceId].planAction" rows="3" maxlength="10000" class="q-input" />
        <div class="q-actions">
          <Button type="submit" label="Enregistrer mon retour" icon="pi pi-save" :loading="enregistrement === section.sectionInstanceId" />
        </div>
      </form>
    </Card>
  </div>
</template>
