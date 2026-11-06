<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import MediaPreview from '../components/traces/MediaPreview.vue';
import traceService, { messageErreur } from '../requests/traceService';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const trace = ref(null);
const chargement = ref(true);
const erreur = ref(null);

const charger = async () => {
  chargement.value = true;
  try {
    const { data } = await traceService.lire(route.params.uuid);
    trace.value = data;
  } catch (e) {
    erreur.value = e?.response?.status === 404 ? 'Cette trace n\'existe pas ou plus.' : messageErreur(e);
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const rattachementsParCompetence = computed(() => {
  const groupes = new Map();
  for (const r of trace.value?.rattachements ?? []) {
    const cle = r.competence?.id ?? 0;
    if (!groupes.has(cle)) groupes.set(cle, { competence: r.competence, items: [] });
    groupes.get(cle).items.push(r);
  }
  return [...groupes.values()];
});

const libelleType = { competence: 'Compétence', niveau: 'Niveau', ac: 'AC' };

const action = async (fn, succes) => {
  try {
    const { data } = await fn();
    trace.value = data;
    toast.add({ severity: 'success', summary: succes, life: 3000 });
  } catch (e) {
    toast.add({ severity: 'warn', summary: 'Action impossible', detail: messageErreur(e), life: 7000 });
  }
};

const supprimer = () => {
  confirm.require({
    message: 'Supprimer définitivement cette trace et toutes ses preuves ?',
    header: 'Supprimer la trace',
    icon: 'pi pi-trash',
    acceptProps: { label: 'Supprimer', severity: 'danger' },
    rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await traceService.supprimer(trace.value.uuid);
        toast.add({ severity: 'success', summary: 'Trace supprimée', life: 3000 });
        router.push('/unifolio/traces');
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreur(e), life: 6000 });
      }
    },
  });
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : null);
</script>

<template>
  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Trace indisponible" :description="erreur" />

  <template v-else-if="trace">
    <HeaderComponent
      icon="pi pi-images"
      :titre="trace.titre"
      :description="[trace.scolarite?.annee, trace.scolarite?.anneeUniversitaire, trace.typeActivite?.libelle, formatDate(trace.dateRealisation)].filter(Boolean).join(' · ')"
      :show-back="true"
      back-url="/unifolio/traces"
      color="emerald"
    >
      <template #actions>
        <Tag :value="trace.statut.libelle" :severity="trace.statut.badge" />
        <Button v-if="trace.modifiable" label="Modifier" icon="pi pi-pencil" @click="router.push(`/unifolio/traces/${trace.uuid}/modifier`)" />
        <Button
          v-if="['brouillon', 'a_reviser'].includes(trace.statut.value)"
          label="Déposer"
          icon="pi pi-send"
          severity="success"
          outlined
          @click="action(() => traceService.deposer(trace.uuid), 'Trace déposée')"
        />
        <Button
          v-if="trace.statut.value === 'deposee'"
          label="Repasser en brouillon"
          icon="pi pi-undo"
          severity="secondary"
          outlined
          @click="action(() => traceService.remettreEnBrouillon(trace.uuid), 'Trace repassée en brouillon')"
        />
        <Button v-if="trace.statut.value === 'brouillon'" icon="pi pi-trash" severity="danger" text aria-label="Supprimer la trace" @click="supprimer" />
      </template>
    </HeaderComponent>

    <div class="grid gap-4 xl:grid-cols-3">
      <div class="xl:col-span-2 flex flex-col gap-4">
        <section v-if="trace.contexte" class="card p-4">
          <h3 class="text-base font-semibold mt-0">Contexte et démarche</h3>
          <p class="whitespace-pre-line m-0">{{ trace.contexte }}</p>
        </section>

        <section class="card p-4 flex flex-col gap-6">
          <h3 class="text-base font-semibold m-0">Preuves</h3>
          <p v-if="trace.medias.length === 0" class="text-muted-color m-0">Aucune preuve.</p>
          <MediaPreview v-for="media in trace.medias" :key="media.id" :media="media" />
        </section>
      </div>

      <div class="flex flex-col gap-4">
        <section class="card p-4 flex flex-col gap-3">
          <h3 class="text-base font-semibold m-0">Compétences démontrées</h3>
          <p v-if="trace.rattachements.length === 0" class="text-muted-color m-0 text-sm">Pas encore rattachée au référentiel.</p>
          <div v-for="groupe in rattachementsParCompetence" :key="groupe.competence?.id ?? 0" class="flex flex-col gap-2">
            <div class="flex items-center gap-2 font-medium">
              <span class="w-3 h-3 rounded-full" :style="{ background: groupe.competence?.couleur || 'var(--p-primary-color)' }" />
              {{ groupe.competence?.nomCourt ?? 'Compétence' }}
            </div>
            <div v-for="r in groupe.items" :key="r.id" class="pl-5 flex flex-col gap-1">
              <span class="text-sm">
                <Tag :value="r.code || libelleType[r.type]" severity="secondary" class="mr-1" />{{ r.libelle }}
              </span>
              <span v-if="r.argumentaire" class="text-sm text-muted-color whitespace-pre-line">{{ r.argumentaire }}</span>
            </div>
          </div>
        </section>

        <section class="card p-4 flex flex-col gap-2 text-sm">
          <h3 class="text-base font-semibold m-0">Usage de l'IA</h3>
          <span><strong>Conception :</strong> {{ trace.conceptionIa.libelle }}</span>
          <span><strong>Réalisation :</strong> {{ trace.realisationIa.libelle }}</span>
          <span v-if="trace.usageIaDetail" class="text-muted-color whitespace-pre-line">{{ trace.usageIaDetail }}</span>
        </section>
      </div>
    </div>
  </template>
</template>
