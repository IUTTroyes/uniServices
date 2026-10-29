<script setup lang="ts">
import { computed } from "vue";
import { getStatutText, getStatutColor } from "@utils";
import { PhotoUser } from "@components";
import Button from "primevue/button";
import Tag from "primevue/tag";

interface Props {
  person: any;
  mode: 'students' | 'staff';
  viewMode: 'grid' | 'list';
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'view-profile', person: any): void;
}>();

const fullName = computed(() => {
  const prenom = props.person?.prenom || '';
  const nom = props.person?.nom || '';
  return `${prenom} ${nom}`.trim() || 'Sans nom';
});

const isStudent = (person: any): boolean => {
  return props.mode === 'students' || 'scolarites' in person;
};
</script>

<template>
  <div
      v-if="viewMode === 'grid'"
      class="bg-white rounded-2xl border border-gray-200 hover:border-primary-400 p-5 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between group"
  >
    <!-- Top section: Photo + Identity -->
    <div class="text-center">
      <div
          class="relative inline-block mb-3 cursor-pointer"
          @click="emit('view-profile', person)"
          v-tooltip.top="`Voir le profil de ${fullName}`"
          :title="`Voir le profil de ${fullName}`"
      >
        <PhotoUser
            :user-photo="person.photoName"
            :type="isStudent(person) ? 'etudiants' : 'personnels'"
            class="rounded-full w-24 h-24 object-cover border-4 border-gray-100 group-hover:border-primary-200 group-hover:ring-4 group-hover:ring-primary-50 shadow-sm mx-auto transition-all duration-300"
        />
      </div>

      <h3
          class="text-base font-bold text-gray-900 group-hover:text-primary-700 transition-colors leading-snug truncate px-1 cursor-pointer"
          @click="emit('view-profile', person)"
          v-tooltip.top="fullName"
          :title="fullName"
      >
        {{ fullName }}
      </h3>

      <div class="mt-1 flex justify-center">
        <a
            v-if="person.mailUniv"
            :href="`mailto:${person.mailUniv}`"
            @click.stop
            class="text-xs text-gray-600 hover:text-primary-700 inline-flex items-center gap-1.5 truncate max-w-full font-medium transition-colors"
            v-tooltip.top="person.mailUniv"
            :title="person.mailUniv"
        >
          <i class="pi pi-envelope text-[11px] text-gray-400 group-hover:text-primary-500 flex-shrink-0"></i>
          <span class="truncate">{{ person.mailUniv }}</span>
        </a>
      </div>
    </div>

    <!-- Middle section: Metadata -->
    <div class="mt-4 pt-3 border-t border-gray-100 flex-1 flex flex-col justify-center">
      <!-- Student Metadata -->
      <div v-if="isStudent(person)" class="space-y-2.5">
        <div class="flex items-center justify-center gap-2 flex-wrap">
          <Tag
              v-if="person.scolarite"
              :value="person.scolarite"
              severity="primary"
              class="!text-xs font-bold px-2.5 py-0.5 rounded-full truncate max-w-[200px]"
              v-tooltip.top="person.scolarite"
              :title="person.scolarite"
          />
          <span
              v-if="person.numEtudiant"
              class="inline-flex items-center text-[11px] font-mono font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200/80 shadow-2xs cursor-default"
              v-tooltip.top="`Numéro étudiant : ${person.numEtudiant}`"
              :title="`Numéro étudiant : ${person.numEtudiant}`"
          >
            N° {{ person.numEtudiant }}
          </span>
        </div>

        <!-- Groups by type with truncation and tooltip -->
        <div class="flex flex-wrap items-center justify-center gap-1.5 pt-0.5">
          <template v-if="person.groupes?.CM?.length">
            <span
                v-for="grp in person.groupes.CM"
                :key="grp"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100/80 text-blue-900 border border-blue-300 shadow-2xs max-w-[140px] cursor-default"
                v-tooltip.top="`Groupe CM : ${grp}`"
                :title="`Groupe CM : ${grp}`"
            >
              <span class="text-[10px] font-black uppercase text-blue-700 flex-shrink-0">CM</span>
              <span class="truncate">{{ grp }}</span>
            </span>
          </template>
          <template v-if="person.groupes?.TD?.length">
            <span
                v-for="grp in person.groupes.TD"
                :key="grp"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100/80 text-emerald-900 border border-emerald-300 shadow-2xs max-w-[140px] cursor-default"
                v-tooltip.top="`Groupe TD : ${grp}`"
                :title="`Groupe TD : ${grp}`"
            >
              <span class="text-[10px] font-black uppercase text-emerald-700 flex-shrink-0">TD</span>
              <span class="truncate">{{ grp }}</span>
            </span>
          </template>
          <template v-if="person.groupes?.TP?.length">
            <span
                v-for="grp in person.groupes.TP"
                :key="grp"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100/80 text-amber-950 border border-amber-300 shadow-2xs max-w-[140px] cursor-default"
                v-tooltip.top="`Groupe TP : ${grp}`"
                :title="`Groupe TP : ${grp}`"
            >
              <span class="text-[10px] font-black uppercase text-amber-800 flex-shrink-0">TP</span>
              <span class="truncate">{{ grp }}</span>
            </span>
          </template>
          <span
              v-if="!person.groupes?.CM?.length && !person.groupes?.TD?.length && !person.groupes?.TP?.length"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
          >
            <i class="pi pi-info-circle text-[11px] text-gray-500"></i>
            <span>Aucun groupe</span>
          </span>
        </div>
      </div>

      <!-- Staff Metadata -->
      <div v-else class="space-y-2 text-center">
        <div class="flex items-center justify-center">
          <Tag
              :value="person.displayStatut || getStatutText(person.statut)"
              :severity="getStatutColor(person.statut)"
              class="!text-xs font-bold px-2.5 py-0.5 rounded-full truncate max-w-[200px]"
              v-tooltip.top="person.displayStatut || getStatutText(person.statut)"
              :title="person.displayStatut || getStatutText(person.statut)"
          />
        </div>

        <div class="text-xs text-gray-600 space-y-1 pt-1 font-medium">
          <p
              v-if="person.bureau"
              class="flex items-center justify-center gap-1.5 text-gray-700 cursor-default"
              v-tooltip.top="`Bureau : ${person.bureau}`"
              :title="`Bureau : ${person.bureau}`"
          >
            <i class="pi pi-building text-primary-600 text-xs flex-shrink-0"></i>
            <span class="truncate max-w-[180px]">Bureau {{ person.bureau }}</span>
          </p>
          <p
              v-if="person.telBureau"
              class="flex items-center justify-center gap-1.5 text-gray-700 cursor-default"
              v-tooltip.top="`Téléphone : ${person.telBureau}`"
              :title="`Téléphone : ${person.telBureau}`"
          >
            <i class="pi pi-phone text-primary-600 text-xs flex-shrink-0"></i>
            <span class="truncate max-w-[180px]">{{ person.telBureau }}</span>
          </p>
          <p
              v-if="person.domaines"
              class="text-[11px] text-gray-600 line-clamp-2 px-1 cursor-help"
              v-tooltip.top="person.domaines"
              :title="person.domaines"
          >
            {{ person.domaines }}
          </p>
        </div>
      </div>
    </div>

    <!-- Bottom section: Action button to access profile -->
    <div class="mt-4 pt-3 border-t border-gray-100">
      <Button
          type="button"
          icon="pi pi-id-card"
          label="Voir le profil"
          size="small"
          class="w-full !text-xs !py-2 !px-3.5 !rounded-xl !font-semibold !bg-primary-50 !text-primary-700 !border !border-primary-200 hover:!bg-primary-600 hover:!text-white hover:!border-primary-600 transition-all duration-200 shadow-2xs hover:shadow-xs flex items-center justify-center gap-2 cursor-pointer"
          @click.stop="emit('view-profile', person)"
      />
    </div>
  </div>

  <!-- List view mode -->
  <div
      v-else
      class="bg-white rounded-xl border border-gray-200 hover:border-primary-400 p-4 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between gap-4 group"
  >
    <!-- Left: Photo + Name + Mail -->
    <div class="flex items-center gap-4 min-w-0">
      <div
          class="cursor-pointer flex-shrink-0"
          @click="emit('view-profile', person)"
          v-tooltip.top="`Voir le profil de ${fullName}`"
          :title="`Voir le profil de ${fullName}`"
      >
        <PhotoUser
            :user-photo="person.photoName"
            :type="isStudent(person) ? 'etudiants' : 'personnels'"
            class="rounded-full w-14 h-14 object-cover border-2 border-gray-200 group-hover:border-primary-300 shadow-sm flex-shrink-0 transition-colors"
        />
      </div>
      <div class="min-w-0">
        <h3
            class="text-base font-bold text-gray-900 group-hover:text-primary-700 transition-colors leading-tight truncate cursor-pointer"
            @click="emit('view-profile', person)"
            v-tooltip.top="fullName"
            :title="fullName"
        >
          {{ fullName }}
        </h3>
        <a
            v-if="person.mailUniv"
            :href="`mailto:${person.mailUniv}`"
            @click.stop
            class="text-xs text-gray-600 hover:text-primary-700 inline-flex items-center gap-1.5 truncate max-w-full mt-0.5 font-medium transition-colors"
            v-tooltip.top="person.mailUniv"
            :title="person.mailUniv"
        >
          <i class="pi pi-envelope text-[11px] text-gray-400 group-hover:text-primary-500 flex-shrink-0"></i>
          <span class="truncate">{{ person.mailUniv }}</span>
        </a>
      </div>
    </div>

    <!-- Right: Badges, Details and Action Button -->
    <div class="flex items-center gap-3 flex-shrink-0">
      <template v-if="isStudent(person)">
        <Tag
            v-if="person.scolarite"
            :value="person.scolarite"
            severity="primary"
            class="!text-xs font-bold px-2.5 py-0.5 rounded-full truncate max-w-[220px]"
            v-tooltip.top="person.scolarite"
            :title="person.scolarite"
        />

        <div class="hidden sm:flex items-center gap-1.5">
          <span
              v-for="grp in person.groupes?.CM || []"
              :key="grp"
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-100/80 text-blue-900 border border-blue-300 shadow-2xs max-w-[130px] cursor-default"
              v-tooltip.top="`Groupe CM : ${grp}`"
              :title="`Groupe CM : ${grp}`"
          >
            <span class="text-[10px] font-black uppercase text-blue-700 flex-shrink-0">CM</span>
            <span class="truncate">{{ grp }}</span>
          </span>
          <span
              v-for="grp in person.groupes?.TD || []"
              :key="grp"
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-100/80 text-emerald-900 border border-emerald-300 shadow-2xs max-w-[130px] cursor-default"
              v-tooltip.top="`Groupe TD : ${grp}`"
              :title="`Groupe TD : ${grp}`"
          >
            <span class="text-[10px] font-black uppercase text-emerald-700 flex-shrink-0">TD</span>
            <span class="truncate">{{ grp }}</span>
          </span>
          <span
              v-for="grp in person.groupes?.TP || []"
              :key="grp"
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-100/80 text-amber-950 border border-amber-300 shadow-2xs max-w-[130px] cursor-default"
              v-tooltip.top="`Groupe TP : ${grp}`"
              :title="`Groupe TP : ${grp}`"
          >
            <span class="text-[10px] font-black uppercase text-amber-800 flex-shrink-0">TP</span>
            <span class="truncate">{{ grp }}</span>
          </span>
          <span
              v-if="!person.groupes?.CM?.length && !person.groupes?.TD?.length && !person.groupes?.TP?.length"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
          >
            Aucun groupe
          </span>
        </div>

        <span
            v-if="person.numEtudiant"
            class="inline-flex items-center text-xs font-mono font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200/80 hidden md:inline shadow-2xs cursor-default"
            v-tooltip.top="`Numéro étudiant : ${person.numEtudiant}`"
            :title="`Numéro étudiant : ${person.numEtudiant}`"
        >
          N° {{ person.numEtudiant }}
        </span>
      </template>

      <template v-else>
        <Tag
            :value="person.displayStatut || getStatutText(person.statut)"
            :severity="getStatutColor(person.statut)"
            class="!text-xs font-bold px-2.5 py-0.5 rounded-full truncate max-w-[200px]"
            v-tooltip.top="person.displayStatut || getStatutText(person.statut)"
            :title="person.displayStatut || getStatutText(person.statut)"
        />
        <span
            v-if="person.bureau"
            class="text-xs text-gray-700 font-medium hidden sm:inline-flex items-center gap-1 cursor-default"
            v-tooltip.top="`Bureau : ${person.bureau}`"
            :title="`Bureau : ${person.bureau}`"
        >
          <i class="pi pi-building text-primary-600 flex-shrink-0"></i>
          <span class="truncate max-w-[150px]">Bureau {{ person.bureau }}</span>
        </span>
        <span
            v-if="person.telBureau"
            class="text-xs text-gray-700 font-medium hidden md:inline-flex items-center gap-1 cursor-default"
            v-tooltip.top="`Téléphone : ${person.telBureau}`"
            :title="`Téléphone : ${person.telBureau}`"
        >
          <i class="pi pi-phone text-primary-600 flex-shrink-0"></i>
          <span class="truncate max-w-[150px]">{{ person.telBureau }}</span>
        </span>
      </template>

      <!-- Action button to access profile -->
      <Button
          type="button"
          icon="pi pi-id-card"
          label="Voir le profil"
          size="small"
          class="!text-xs !py-2 !px-3.5 !rounded-xl !font-semibold !bg-primary-50 !text-primary-700 !border !border-primary-200 hover:!bg-primary-600 hover:!text-white hover:!border-primary-600 transition-all duration-200 shadow-2xs hover:shadow-xs flex items-center gap-2 cursor-pointer ml-1"
          @click.stop="emit('view-profile', person)"
      />
    </div>
  </div>
</template>

<style scoped></style>
