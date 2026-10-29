<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import IndicateursDashboard from './IndicateursDashboard.vue';
import { getEnseignementsService } from '@requests';
import {useUsersStore} from '@stores';

const enseignements = ref([]);
const usersStore = useUsersStore();
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const departement = usersStore.departementDefaut

onMounted(async () => {
  try {
    await getEnseignements();
  } catch (error) {
    console.error('Erreur lors de la récupération des enseignements :', error);
  }
});

const getEnseignements = async () => {
  try {
    const params = {
      personnel: usersStore.user.id,
      anneeUniversitaire: anneeUniv.id,
      departement: departement?.id ?? null,
    };
    enseignements.value = await getEnseignementsService(params, '/dashboard');
  } catch (error) {
    console.error('Erreur lors de la récupération des enseignements :', error);
  } finally {
    console.log('Enseignements récupérés :', enseignements.value);
  }
};

// Le diplôme est exposé sous forme de sigle par l'API du dashboard.
const diplomeSelectionne = ref(null);
const typeSelectionne = ref('tous');
const libellesTypes = { ressource: 'Ressources', sae: 'SAÉ', matiere: 'Matières' };
const diplomes = computed(() => {
  const groupes = new Map();
  for (const enseignement of enseignements.value) {
    const diplome = enseignement.diplome?.trim() || 'Diplôme non renseigné';
    if (!groupes.has(diplome)) groupes.set(diplome, { id: diplome, libelle: diplome, enseignements: [] });
    groupes.get(diplome).enseignements.push(enseignement);
  }
  return [...groupes.values()];
});
const enseignementsDuDiplome = computed(() => diplomes.value.find(item => item.id === diplomeSelectionne.value)?.enseignements ?? []);
const typesDisponibles = computed(() => {
  const counts = new Map();
  for (const enseignement of enseignementsDuDiplome.value) {
    const type = enseignement.type || 'non-renseigne';
    counts.set(type, (counts.get(type) ?? 0) + 1);
  }
  return [...counts].map(([value, count]) => ({ value, label: `${libellesTypes[value] ?? (value === 'non-renseigne' ? 'Type non renseigné' : value)} (${count})` }));
});
const filtresTypes = computed(() => [{ value: 'tous', label: `Tous (${enseignementsDuDiplome.value.length})` }, ...typesDisponibles.value]);
const enseignementsFiltres = computed(() => enseignementsDuDiplome.value.filter(item => typeSelectionne.value === 'tous' || (item.type || 'non-renseigne') === typeSelectionne.value));
watch(diplomes, (items) => {
  if (!items.some(item => item.id === diplomeSelectionne.value)) diplomeSelectionne.value = items[0]?.id ?? null;
}, { immediate: true });
watch(diplomeSelectionne, () => { typeSelectionne.value = 'tous'; });
watch(typesDisponibles, (items) => {
  if (!items.some(item => item.value === typeSelectionne.value)) typeSelectionne.value = 'tous';
});

// Données de démonstration : à remplacer par les enseignements de l'utilisateur connecté.
const teachings = [
  { id: 'R1.01', label: 'Initiation au développement', formation: 'BUT Informatique · S1', groups: 'TP A, TP B', planned: 64, completed: 28, plan: 'Objectifs : concevoir un algorithme, utiliser les structures de contrôle et tester un programme.', absences: [{ student: 'Camille Martin', date: '27/10', status: 'Justifiée' }, { student: 'Alex Dubois', date: '29/10', status: 'En attente' }] },
  { id: 'R3.01', label: 'Développement web', formation: 'BUT Informatique · S3', groups: 'TD 1, TD 2', planned: 48, completed: 32, plan: 'Objectifs : développer une interface accessible, consommer une API et organiser les composants.', absences: [{ student: 'Lou Bernard', date: '29/10', status: 'Non justifiée' }] },
  { id: 'SAÉ 5.01', label: 'Développement avancé', formation: 'BUT Informatique · S5', groups: 'Promotion entière', planned: 40, completed: 12, plan: 'Objectifs : piloter un projet en équipe et livrer une application documentée.', absences: [] },
];
const evaluations = [
  { id: 1, teaching: 'R1.01', label: 'Contrôle algorithmique', group: 'TP A', entered: 18, expected: 26, due: '30 octobre', status: 'À compléter' },
  { id: 2, teaching: 'R3.01', label: 'Projet interface web', group: 'TD 1', entered: 0, expected: 28, due: '3 novembre', status: 'À saisir' },
  { id: 3, teaching: 'R1.01', label: 'Travaux pratiques', group: 'TP B', entered: 26, expected: 26, due: '23 octobre', status: 'Terminée' },
];
const messages = [
  { id: 1, sender: 'Scolarité', subject: 'Vérification des listes de groupes', preview: 'Merci de vérifier la composition des groupes avant vendredi.', date: '10:24', unread: true },
  { id: 2, sender: 'Camille Martin', subject: 'Question sur le projet web', preview: 'Bonjour, pouvons-nous utiliser une bibliothèque pour les graphiques ?', date: '09:15', unread: true },
  { id: 3, sender: 'Équipe pédagogique', subject: 'Réunion de coordination', preview: 'La prochaine réunion se tiendra mardi à 14 h en salle du conseil.', date: 'Hier', unread: false },
];
const reminders = computed(() => evaluations.filter(item => item.entered < item.expected));
const workload = computed(() => {
  const planned = teachings.reduce((sum, item) => sum + item.planned, 0);
  const completed = teachings.reduce((sum, item) => sum + item.completed, 0);
  return { planned, completed, remaining: planned - completed, percentage: Math.round(completed / planned * 100) };
});
const metrics = computed(() => [
  { label: 'Heures prévues', value: `${workload.value.planned} h`, icon: 'pi pi-calendar' },
  { label: 'Heures réalisées', value: `${workload.value.completed} h`, icon: 'pi pi-check-circle' },
  { label: 'Reste à réaliser', value: `${workload.value.remaining} h`, icon: 'pi pi-clock' },
  { label: 'Service réalisé', value: `${workload.value.percentage} %`, icon: 'pi pi-chart-bar' },
]);
const shortcuts = [
  { key: 'notes', label: 'Notes', icon: 'pi pi-pencil' },
  { key: 'absences', label: 'Absences', icon: 'pi pi-user-minus' },
  { key: 'plan', label: 'Plan de cours', icon: 'pi pi-book' },
  { key: 'previsionnel', label: 'Prévisionnel', icon: 'pi pi-calendar' },
];
const selectedTeaching = ref(null);
const selectedShortcut = ref(null);
const dialogVisible = ref(false);
const selectedMessage = ref(null);
const messageVisible = ref(false);
function openTeaching(teaching, shortcut) {
  selectedTeaching.value = teaching;
  selectedShortcut.value = shortcuts.find(item => item.key === shortcut);
  dialogVisible.value = true;
}
</script>

<template>
  <section aria-labelledby="teacher-dashboard" class="flex flex-col gap-4">
    <h2 id="teacher-dashboard" class="m-0! text-xl! flex items-center gap-2"><i class="pi pi-user text-primary" aria-hidden="true" />Mon activité d’enseignant</h2>
    <article class="card p-5! mb-0!">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h3 class="m-0! text-lg!">Mes enseignements</h3>
        <span class="text-sm text-muted-color">{{ enseignements.length }} enseignements</span>
      </div>
      <Tabs v-if="diplomes.length" v-model:value="diplomeSelectionne" scrollable>
        <TabList aria-label="Diplômes de mes enseignements">
          <Tab v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
            <span>{{ diplome.libelle }}</span>
            <span class="ml-2 text-xs text-muted-color">({{ diplome.enseignements.length }})</span>
          </Tab>
        </TabList>
        <TabPanels class="px-0! pb-0!">
          <TabPanel v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
            <template v-if="diplome.id === diplomeSelectionne">
              <div v-if="typesDisponibles.length > 1" class="flex flex-wrap items-center gap-3 mb-4">
                <span id="types-enseignements" class="text-sm text-muted-color">Type d’enseignement</span>
                <SelectButton v-model="typeSelectionne" :options="filtresTypes" option-label="label" option-value="value" :allow-empty="false" aria-labelledby="types-enseignements" size="small" class="flex-wrap" />
              </div>
              <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <article v-for="enseignement in enseignementsFiltres" :key="enseignement.id" class="p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col gap-2">
                  <div><span class="uppercase font-semibold text-xs text-muted-color">{{ enseignement.type }} - </span><span class="text-xs font-semibold text-primary">{{ enseignement.codeEnseignement }}</span></div>
                  <h4 class="m-0! text-base!">{{ enseignement.libelle }}</h4>
                  <div class="flex flex-wrap gap-1 mt-auto">
                    <!-- TODO : raccorder les accès à la logique métier de chaque enseignement. -->
                    <Button v-for="shortcut in shortcuts" :key="shortcut.key" :label="shortcut.label" :icon="shortcut.icon" severity="secondary" text size="small" :aria-label="`${shortcut.label} — ${enseignement.libelle}`" @click="openTeaching(enseignement, shortcut.key)" />
                  </div>
                </article>
              </div>
            </template>
          </TabPanel>
        </TabPanels>
      </Tabs>
      <p v-else class="m-0! text-sm text-muted-color">Aucun enseignement disponible pour ce contexte.</p>
    </article>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <article class="card p-5! mb-0!">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h3 class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-pencil text-primary" aria-hidden="true" />Notes à saisir</h3>
          <Tag :value="`${reminders.length} évaluations`" severity="warn" />
        </div>
        <ul class="list-none m-0! p-0! flex flex-col divide-y divide-surface-200 dark:divide-surface-700">
          <li v-for="evaluation in reminders" :key="evaluation.id" class="py-3 flex flex-wrap items-center justify-between gap-3">
            <div><p class="m-0! font-semibold">{{ evaluation.label }}</p><p class="m-0! text-sm text-muted-color">{{ evaluation.teaching }} · {{ evaluation.group }} · échéance {{ evaluation.due }}</p><p class="mt-1! mb-0! text-sm">{{ evaluation.entered }} / {{ evaluation.expected }} notes renseignées</p></div>
            <Button label="Consulter" icon="pi pi-arrow-right" size="small" severity="secondary" @click="openTeaching(teachings.find(item => item.id === evaluation.teaching), 'notes')" />
          </li>
        </ul>
      </article>
      <article class="card p-5! mb-0!">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h3 class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-envelope text-primary" aria-hidden="true" />Ma messagerie</h3>
          <Tag :value="`${messages.filter(item => item.unread).length} non lus`" severity="info" />
        </div>
        <ul class="list-none m-0! p-0! flex flex-col gap-2">
          <li v-for="message in messages" :key="message.id">
            <button type="button" class="w-full text-left p-3 rounded-lg bg-surface-50 dark:bg-surface-800 hover:bg-surface-100 dark:hover:bg-surface-700 focus-visible:outline-2 focus-visible:outline-primary" @click="selectedMessage = message; messageVisible = true">
              <span class="flex items-center justify-between gap-3 text-sm"><span class="font-semibold">{{ message.sender }}<span v-if="message.unread" class="ml-2 text-primary text-xs">Non lu</span></span><span class="text-muted-color">{{ message.date }}</span></span>
              <span class="block mt-1 text-sm">{{ message.subject }}</span>
              <span class="block text-xs text-muted-color truncate">{{ message.preview }}</span>
            </button>
          </li>
        </ul>
      </article>
    </div>
    <article class="card p-5! mb-0!">
      <h3 class="mt-0! mb-4! text-lg!">Mon service prévisionnel</h3>
      <IndicateursDashboard :items="metrics" />
      <ProgressBar :value="workload.percentage" :show-value="false" class="mt-4 h-2!" :aria-label="`${workload.percentage} % du service réalisé`" />
      <p class="mt-2! mb-0! text-sm text-muted-color">Heures d’enseignement de démonstration, hors primes et équivalences.</p>
    </article>

    <Dialog v-model:visible="dialogVisible" modal :header="`${selectedShortcut?.label ?? ''} · ${selectedTeaching?.id ?? ''}`" class="w-full max-w-2xl mx-4">
      <template v-if="selectedTeaching">
        <p class="mt-0! font-semibold">{{ selectedTeaching.label }} · {{ selectedTeaching.groups }}</p>
        <template v-if="selectedShortcut.key === 'notes'">
          <div v-for="evaluation in evaluations.filter(item => item.teaching === selectedTeaching.id)" :key="evaluation.id" class="p-3 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800 flex justify-between gap-3"><span>{{ evaluation.label }} · {{ evaluation.group }}</span><span>{{ evaluation.entered }} / {{ evaluation.expected }} notes</span></div>
          <p v-if="!evaluations.some(item => item.teaching === selectedTeaching.id)" class="text-muted-color">Aucune évaluation prévue pour cet enseignement.</p>
        </template>
        <template v-else-if="selectedShortcut.key === 'absences'">
          <div v-for="absence in selectedTeaching.absences" :key="absence.student" class="p-3 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800 flex flex-wrap justify-between gap-2"><span>{{ absence.student }} · {{ absence.date }}</span><Tag :value="absence.status" :severity="absence.status === 'Justifiée' ? 'success' : 'warn'" /></div>
          <p v-if="!selectedTeaching.absences.length" class="text-muted-color">Aucune absence signalée pour cet enseignement.</p>
        </template>
        <p v-else-if="selectedShortcut.key === 'plan'">{{ selectedTeaching.plan }}</p>
        <IndicateursDashboard v-else :items="[{ label: 'Prévues', value: `${selectedTeaching.planned} h` }, { label: 'Réalisées', value: `${selectedTeaching.completed} h` }, { label: 'Restantes', value: `${selectedTeaching.planned - selectedTeaching.completed} h` }]" />
        <p class="mb-0! mt-4! text-xs text-muted-color">Aperçu de démonstration · aucune modification enregistrée.</p>
      </template>
    </Dialog>
    <Dialog v-model:visible="messageVisible" modal :header="selectedMessage?.subject" class="w-full max-w-xl mx-4">
      <template v-if="selectedMessage"><p class="mt-0! text-sm text-muted-color">{{ selectedMessage.sender }} · {{ selectedMessage.date }}</p><p>{{ selectedMessage.preview }}</p><p class="text-xs text-muted-color">Message de démonstration.</p></template>
    </Dialog>
  </section>
</template>
