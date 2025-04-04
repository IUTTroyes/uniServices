<script setup>
import { ref, onMounted, computed } from "vue";
import { useUsersStore } from "@stores";
import { formatDateLong } from "@helpers/date.js";
import { getActualitesService, getAgendaService } from "@requests";
import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";
import EdtJour from "@/components/Edt/EdtJour.vue";
import {CardSkeleton, ArticleSkeleton} from "@components";

const userStore = useUsersStore();
const initiales = ref("");
const absences = ref([]);
const date = new Date();
const actuEvents = ref([]);
const agendaEvents = ref([]);
const isLoadingActu = ref(true);
const isLoadingAgenda = ref(true);

const userInitiales = computed(
    () => userStore.user?.prenom?.charAt(0) + userStore.user?.nom?.charAt(0) || ""
);

const getActualites = async () => {
  try {
    actuEvents.value = await getActualitesService();
  } catch (error) {
    console.error("Error fetching actualites:", error);
  } finally {
    isLoadingActu.value = false;
  }
};

const getAgenda = async () => {
  try {
    agendaEvents.value = await getAgendaService();
  } catch (error) {
    console.error("Error fetching agenda:", error);
  } finally {
    isLoadingAgenda.value = false;
  }
};

const generateMockAbsences = () => [
  { semestre: "S1", etudiant: "John Doe", heure: "08:00", prof: "Jane Doe", matiere: "Maths" },
  { semestre: "S1", etudiant: "John Doe", heure: "08:00", prof: "Jane Doe", matiere: "Maths" },
  { semestre: "S1", etudiant: "John Doe", heure: "08:00", prof: "Jane Doe", matiere: "Maths" },
  { semestre: "S1", etudiant: "John Doe", heure: "08:00", prof: "Jane Doe", matiere: "Maths" }
];

onMounted(async () => {
  await Promise.all([getActualites(), getAgenda()]);
  initiales.value = userInitiales.value;
  absences.value = generateMockAbsences();

  console.log(userStore.userType)
});

const redirectTo = (link) => {
  window.open(link, "_blank");
};
</script>

<template>
  <div>
    <div class="m-5 mb-10">
      <div class="w-1/2" v-if="userStore.isLoading">
        <ArticleSkeleton/>
      </div>
      <div v-else class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-20 bg-violet-400 rounded-full flex items-center justify-center">
            <template v-if="userStore.userPhoto">
              <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full" />
            </template>
            <template v-else>
              <span class="text-gray-700 text-xl">{{ initiales }}</span>
            </template>
          </div>
          <div class="ml-4">
            <h1 class="text-2xl font-semibold">
              <span class="font-light">Bonjour,</span> {{ userStore.user.prenom }}
            </h1>
            <small>{{ formatDateLong(date) }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title mb-4">
        <div class="font-semibold text-xl">Aujourd'hui</div>
      </div>
      <div class="card-content flex flex-col gap-16">
        <div>
          <div class="text-lg text-muted-color font-semibold mb-2">Mes cours à venir</div>
          <EdtJour />
        </div>
        <hr v-if="userStore.isAssistant">
        <div v-if="userStore.isAssistant" class="absences flex flex-col gap-2">
          <div class="text-lg text-muted-color font-semibold">Suivi des absences entre {{ new Date().getHours() < 13 ? '8h00 et 13h00' : '13h00 et 21h00' }}</div>

          <Message severity="info" icon="pi pi-info-circle">{{ absences.length }} absent.s sur la demie journée en cours.</Message>

          <DataTable :value="absences" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" stripedRows showGridlines tableStyle="min-width: 50rem"
                     paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                     currentPageReportTemplate="{first} to {last} of {totalRecords}">
            <template #paginatorstart>
              <Button type="button" icon="pi pi-refresh" text />
            </template>
            <template #paginatorend>
              <Button type="button" icon="pi pi-download" text />
            </template>
            <Column field="semestre" header="Semestre" sortable style="width: 25%"></Column>
            <Column field="etudiant" header="Etudiant" sortable style="width: 25%"></Column>
            <Column field="heure" header="Heure" sortable style="width: 25%"></Column>
            <Column field="prof" header="Prof." sortable style="width: 25%"></Column>
            <Column field="matiere" header="Matière" sortable style="width: 25%"></Column>
          </DataTable>
        </div>
      </div>
    </div>

    <div class="card h-full">
      <div class="card-title mb-4">
        <div class="font-semibold text-xl">Agenda de l'IUT</div>
        <em>Les évènements à venir</em>
      </div>
      <div class="card-content flex justify-between gap-10 mb-8">
        <div v-if="isLoadingAgenda" class="flex justify-between w-full gap-10">
          <ArticleSkeleton />
          <ArticleSkeleton />
          <ArticleSkeleton />
          <ArticleSkeleton />
        </div>
        <div v-else v-for="(event, index) in agendaEvents" class="p-4 rounded-md flex-1 flex flex-col justify-between gap-2">
          <div class="flex flex-col gap-2 items-start">
            <div>
              <div class="font-bold text-lg">
                {{ event.title }}
              </div>
              <div class="text-sm text-muted-color">
                {{ event.date }}
              </div>
            </div>
            <div>
              <p>
                {{ event.content }}
              </p>
            </div>
          </div>
          <Button class="bg-primary-light" label="En savoir plus" icon="pi pi-external-link" iconPos="right" @click="redirectTo(event.link)"/>
        </div>
      </div>

      <div class="card-title mb-4">
        <div class="font-semibold text-xl">Actualités</div>
        <em>Les évènements passés</em>
      </div>
      <div class="flex justify-between gap-10">
        <div v-if="isLoadingActu" class="flex justify-between w-full gap-10">
          <CardSkeleton />
          <CardSkeleton />
          <CardSkeleton />
          <CardSkeleton />
        </div>
        <Card v-else v-for="(event, index) in actuEvents" :key="index" style="width: 25rem" class="card-actus flex flex-col justify-between overflow-hidden w-25">
          <template #header>
            <div class="h-32 w-full overflow-hidden flex items-center">
              <img :alt="event.title" :src="event.image" />
            </div>
            <div class="text-lg font-bold px-5 pt-3">{{event.title}}</div>
            <div class="text-sm px-5 text-muted-color">{{event.date}}</div>
          </template>
          <template #content>
            <div class="flex flex-col justify-between gap-2">
              <div>{{event.content}}</div>
              <Button label="En savoir plus" icon="pi pi-external-link" iconPos="right" class="bg-primary-light" @click="redirectTo(event.link)"/>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <DashboardPersonnel v-if="userStore.isPersonnel" />
    <DashboardEtudiant v-else-if="userStore.isEtudiant" />
  </div>
</template>

<style scoped>
.bg-primary-light {
  background-color: var(--p-tag-primary-background);
  border: 1px solid var(--p-tag-primary-background);
  color: var(--primary-color);
}

.card-actus {
  border: solid 1px var(--p-tag-primary-background);
  box-shadow: none;
}
</style>
