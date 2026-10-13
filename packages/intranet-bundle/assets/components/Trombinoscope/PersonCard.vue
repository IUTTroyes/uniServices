<script setup lang="ts">
import { getStatutText, getStatutColor } from "@utils";
import { PhotoUser } from "@components";

interface Props {
  person: any;
  mode: 'students' | 'staff';
  viewMode: 'grid' | 'list';
}

const props = defineProps<Props>();

const isStudent = (person: any): boolean => {
  return props.mode === 'students' || 'scolarites' in person;
};
</script>

<template>
  <div
      v-if="viewMode === 'grid'"
      class="bg-white rounded-2xl border border-gray-200/80 hover:border-primary-400 p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between group"
  >
    <!-- Top section: Photo + Identity -->
    <div class="text-center">
      <div class="relative inline-block mb-3">
        <PhotoUser
            :user-photo="person.photoName"
            :type="isStudent(person) ? 'etudiants' : 'personnels'"
            class="rounded-full w-24 h-24 object-cover border-4 border-gray-100 group-hover:border-primary-100 shadow-sm mx-auto transition-colors"
        />
        <span
            v-if="isStudent(person) && person.status === 'active'"
            class="absolute bottom-1 right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"
            title="Scolarité active"
        ></span>
      </div>

      <h3 class="text-base font-semibold text-gray-900 group-hover:text-primary-700 transition-colors leading-tight truncate px-1">
        {{ person.prenom }} {{ person.nom }}
      </h3>

      <div class="mt-1">
        <a
            v-if="person.mailUniv"
            :href="`mailto:${person.mailUniv}`"
            class="text-xs text-gray-500 hover:text-primary-600 inline-flex items-center gap-1 truncate max-w-full"
            :title="person.mailUniv"
        >
          <i class="pi pi-envelope text-[11px]"></i>
          <span class="truncate">{{ person.mailUniv }}</span>
        </a>
      </div>
    </div>

    <!-- Middle section: Metadata -->
    <div class="mt-4 pt-3 border-t border-gray-100 flex-1 flex flex-col justify-center">
      <!-- Student Metadata -->
      <div v-if="isStudent(person)" class="space-y-2">
        <div class="flex items-center justify-center gap-1.5 flex-wrap">
          <Tag
              v-if="person.scolarite"
              :value="person.scolarite"
              severity="primary"
              class="!text-xs font-semibold px-2.5 py-0.5 rounded-full"
          />
          <span
              v-if="person.numEtudiant"
              class="text-[11px] text-gray-400 font-mono"
              title="Numéro étudiant"
          >
            #{{ person.numEtudiant }}
          </span>
        </div>

        <!-- Groups by type -->
        <div class="flex flex-wrap items-center justify-center gap-1 pt-1">
          <template v-if="person.groupes?.CM?.length">
            <span
                v-for="grp in person.groupes.CM"
                :key="grp"
                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/60"
                title="Groupe CM"
            >
              CM {{ grp }}
            </span>
          </template>
          <template v-if="person.groupes?.TD?.length">
            <span
                v-for="grp in person.groupes.TD"
                :key="grp"
                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                title="Groupe TD"
            >
              TD {{ grp }}
            </span>
          </template>
          <template v-if="person.groupes?.TP?.length">
            <span
                v-for="grp in person.groupes.TP"
                :key="grp"
                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60"
                title="Groupe TP"
            >
              TP {{ grp }}
            </span>
          </template>
          <span
              v-if="!person.groupes?.CM?.length && !person.groupes?.TD?.length && !person.groupes?.TP?.length"
              class="text-xs text-gray-400 italic"
          >
            Sans groupe
          </span>
        </div>
      </div>

      <!-- Staff Metadata -->
      <div v-else class="space-y-2 text-center">
        <div class="flex items-center justify-center">
          <Tag
              :value="person.displayStatut || getStatutText(person.statut)"
              :severity="getStatutColor(person.statut)"
              class="!text-xs font-semibold px-2.5 py-0.5 rounded-full"
          />
        </div>

        <div class="text-xs text-gray-500 space-y-0.5 pt-1">
          <p v-if="person.bureau" class="flex items-center justify-center gap-1 text-gray-600">
            <i class="pi pi-building text-gray-400 text-xs"></i>
            Bureau {{ person.bureau }}
          </p>
          <p v-if="person.telBureau" class="flex items-center justify-center gap-1 text-gray-600">
            <i class="pi pi-phone text-gray-400 text-xs"></i>
            {{ person.telBureau }}
          </p>
          <p v-if="person.domaines" class="text-[11px] text-gray-500 line-clamp-2 px-1">
            {{ person.domaines }}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- List view mode -->
  <div
      v-else
      class="bg-white rounded-xl border border-gray-200/80 hover:border-primary-400 p-4 shadow-sm hover:shadow-md transition-all duration-200 flex items-center justify-between gap-4 group"
  >
    <!-- Left: Photo + Name + Mail -->
    <div class="flex items-center gap-4 min-w-0">
      <PhotoUser
          :user-photo="person.photoName"
          :type="isStudent(person) ? 'etudiants' : 'personnels'"
          class="rounded-full w-14 h-14 object-cover border-2 border-gray-100 group-hover:border-primary-100 shadow-sm flex-shrink-0"
      />
      <div class="min-w-0">
        <h3 class="text-base font-semibold text-gray-900 group-hover:text-primary-700 transition-colors leading-tight truncate">
          {{ person.prenom }} {{ person.nom }}
        </h3>
        <a
            v-if="person.mailUniv"
            :href="`mailto:${person.mailUniv}`"
            class="text-xs text-gray-500 hover:text-primary-600 inline-flex items-center gap-1 truncate max-w-full mt-0.5"
        >
          <i class="pi pi-envelope text-[11px]"></i>
          <span>{{ person.mailUniv }}</span>
        </a>
      </div>
    </div>

    <!-- Right: Badges and Details -->
    <div class="flex items-center gap-3 flex-shrink-0">
      <template v-if="isStudent(person)">
        <Tag
            v-if="person.scolarite"
            :value="person.scolarite"
            severity="primary"
            class="!text-xs font-semibold px-2.5 py-0.5 rounded-full"
        />

        <div class="hidden sm:flex items-center gap-1">
          <span
              v-for="grp in person.groupes?.CM || []"
              :key="grp"
              class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200/60"
          >
            CM {{ grp }}
          </span>
          <span
              v-for="grp in person.groupes?.TD || []"
              :key="grp"
              class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60"
          >
            TD {{ grp }}
          </span>
          <span
              v-for="grp in person.groupes?.TP || []"
              :key="grp"
              class="px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/60"
          >
            TP {{ grp }}
          </span>
        </div>

        <span v-if="person.numEtudiant" class="text-xs text-gray-400 font-mono hidden md:inline">
          #{{ person.numEtudiant }}
        </span>
      </template>

      <template v-else>
        <Tag
            :value="person.displayStatut || getStatutText(person.statut)"
            :severity="getStatutColor(person.statut)"
            class="!text-xs font-semibold px-2.5 py-0.5 rounded-full"
        />
        <span v-if="person.bureau" class="text-xs text-gray-500 hidden sm:inline flex items-center gap-1">
          <i class="pi pi-building text-gray-400"></i> Bureau {{ person.bureau }}
        </span>
        <span v-if="person.telBureau" class="text-xs text-gray-500 hidden md:inline flex items-center gap-1">
          <i class="pi pi-phone text-gray-400"></i> {{ person.telBureau }}
        </span>
      </template>
    </div>
  </div>
</template>
