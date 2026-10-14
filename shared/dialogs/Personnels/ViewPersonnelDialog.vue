<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { PhotoUser } from '@components';
import { getStatutText, getStatutColor } from '@utils';
import { getPersonnelService } from '@requests';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
  visible: {
    type: Boolean,
    default: undefined,
  },
  personnel: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:visible', 'update:isVisible']);

const isModalVisible = computed({
  get: () => (props.visible !== undefined ? props.visible : props.isVisible),
  set: (val) => {
    emit('update:visible', val);
    emit('update:isVisible', val);
  },
});

const toast = useToast();
const loading = ref(false);
const detailedPersonnel = ref(null);

const activePersonnel = computed(() => {
  return detailedPersonnel.value || props.personnel || {};
});

const loadDetails = async (id) => {
  if (!id) return;
  try {
    loading.value = true;
    detailedPersonnel.value = await getPersonnelService(id);
  } catch (err) {
    console.error('Erreur lors du chargement des détails du personnel:', err);
  } finally {
    loading.value = false;
  }
};

watch(
  () => isModalVisible.value,
  (visible) => {
    if (visible && props.personnel?.id) {
      loadDetails(props.personnel.id);
    } else if (!visible) {
      detailedPersonnel.value = null;
    }
  },
  { immediate: true }
);

const copyToClipboard = (text, label = 'Information') => {
  if (!text) return;
  navigator.clipboard
    .writeText(text)
    .then(() => {
      toast?.add({
        severity: 'success',
        summary: 'Copié',
        detail: `${label} copié dans le presse-papiers`,
        life: 3000,
      });
    })
    .catch(() => {});
};
</script>

<template>
  <Dialog
    v-model:visible="isModalVisible"
    modal
    :header="`Profil du personnel — ${activePersonnel.prenom || ''} ${activePersonnel.nom || ''}`"
    :style="{ width: '60vw' }"
    :breakpoints="{ '1199px': '75vw', '575px': '95vw' }"
    dismissable-mask
    :closable="true"
  >
    <div v-if="activePersonnel" class="p-2 md:p-4 space-y-6">
      <!-- Header row: Photo + Identity + Badges -->
      <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 bg-gray-50/80 p-5 rounded-2xl border border-gray-100">
        <div class="relative flex-shrink-0">
          <PhotoUser
            :user-photo="activePersonnel.photoName"
            type="personnels"
            class="rounded-2xl w-28 h-28 object-cover border-4 border-white shadow-md mx-auto"
          />
        </div>

        <div class="flex-1 text-center sm:text-left space-y-2 min-w-0">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                {{ activePersonnel.prenom }} {{ activePersonnel.nom }}
              </h2>
              <p class="text-sm text-gray-500 font-medium mt-0.5">
                {{ activePersonnel.displayStatut || getStatutText(activePersonnel.statut) || 'Personnel de l\'établissement' }}
              </p>
            </div>

            <div class="flex items-center justify-center sm:justify-end gap-2 flex-wrap">
              <Tag
                v-if="activePersonnel.statut"
                :value="activePersonnel.displayStatut || getStatutText(activePersonnel.statut)"
                :severity="getStatutColor(activePersonnel.statut)"
                class="!text-xs font-semibold px-3 py-1 rounded-full"
              />
              <span
                v-if="activePersonnel.numeroHarpege"
                class="text-xs text-gray-400 font-mono bg-white px-2 py-1 rounded-md border border-gray-200"
                title="Numéro Harpège"
              >
                Harpège : {{ activePersonnel.numeroHarpege }}
              </span>
            </div>
          </div>

          <!-- Quick contact row -->
          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 pt-2 text-sm text-gray-600">
            <div
              v-if="activePersonnel.mailUniv"
              class="inline-flex items-center gap-1.5 hover:text-primary-600 transition-colors cursor-pointer group"
              @click="copyToClipboard(activePersonnel.mailUniv, 'Adresse email')"
              title="Cliquer pour copier l'adresse email"
            >
              <i class="pi pi-envelope text-primary-500"></i>
              <a :href="`mailto:${activePersonnel.mailUniv}`" @click.stop class="hover:underline">
                {{ activePersonnel.mailUniv }}
              </a>
              <i class="pi pi-copy text-xs text-gray-400 group-hover:text-primary-600 ml-0.5"></i>
            </div>

            <div v-if="activePersonnel.bureau" class="inline-flex items-center gap-1.5 text-gray-700">
              <i class="pi pi-building text-amber-500"></i>
              <span>Bureau {{ activePersonnel.bureau }}</span>
            </div>

            <div v-if="activePersonnel.telBureau" class="inline-flex items-center gap-1.5 text-gray-700">
              <i class="pi pi-phone text-emerald-500"></i>
              <span>{{ activePersonnel.telBureau }}</span>
              <span v-if="activePersonnel.posteInterne" class="text-xs text-gray-400">
                (Poste {{ activePersonnel.posteInterne }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Information Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Section Coordonnées & Localisation -->
        <div class="bg-white border border-gray-200/80 rounded-xl p-4 space-y-3 shadow-xs">
          <div class="flex items-center gap-2 text-primary-700 font-semibold text-sm border-b pb-2">
            <i class="pi pi-id-card"></i>
            <span>Coordonnées & Poste</span>
          </div>

          <ul class="text-xs space-y-2.5 text-gray-600">
            <li class="flex justify-between items-center py-1 border-b border-gray-50">
              <span class="font-medium text-gray-500">Email universitaire :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.mailUniv || 'Non renseigné' }}</span>
            </li>
            <li v-if="activePersonnel.mailPerso" class="flex justify-between items-center py-1 border-b border-gray-50">
              <span class="font-medium text-gray-500">Email personnel :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.mailPerso }}</span>
            </li>
            <li class="flex justify-between items-center py-1 border-b border-gray-50">
              <span class="font-medium text-gray-500">Bureau :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.bureau || 'Non renseigné' }}</span>
            </li>
            <li class="flex justify-between items-center py-1 border-b border-gray-50">
              <span class="font-medium text-gray-500">Téléphone de bureau :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.telBureau || 'Non renseigné' }}</span>
            </li>
            <li v-if="activePersonnel.posteInterne" class="flex justify-between items-center py-1 border-b border-gray-50">
              <span class="font-medium text-gray-500">Poste interne :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.posteInterne }}</span>
            </li>
            <li v-if="activePersonnel.department || activePersonnel.departement?.libelle" class="flex justify-between items-center py-1">
              <span class="font-medium text-gray-500">Département :</span>
              <span class="font-semibold text-gray-800">{{ activePersonnel.department || activePersonnel.departement?.libelle }}</span>
            </li>
          </ul>
        </div>

        <!-- Section Domaines & Responsabilités -->
        <div class="bg-white border border-gray-200/80 rounded-xl p-4 space-y-3 shadow-xs">
          <div class="flex items-center gap-2 text-primary-700 font-semibold text-sm border-b pb-2">
            <i class="pi pi-briefcase"></i>
            <span>Activités, Domaines & Responsabilités</span>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <span class="font-medium text-gray-500 block mb-1">Domaines d'intervention :</span>
              <div v-if="activePersonnel.domaines" class="flex flex-wrap gap-1">
                <template v-if="Array.isArray(activePersonnel.domaines)">
                  <span
                    v-for="dom in activePersonnel.domaines"
                    :key="dom"
                    class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[11px] font-medium border border-blue-200/60"
                  >
                    {{ dom }}
                  </span>
                </template>
                <template v-else>
                  <span
                    v-for="dom in activePersonnel.domaines.split(',')"
                    :key="dom"
                    class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[11px] font-medium border border-blue-200/60"
                  >
                    {{ dom.trim() }}
                  </span>
                </template>
              </div>
              <p v-else class="text-gray-400 italic">Non renseigné</p>
            </div>

            <div v-if="activePersonnel.responsabilites">
              <span class="font-medium text-gray-500 block mb-1">Responsabilités :</span>
              <p class="text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100 whitespace-pre-line leading-relaxed">
                {{ activePersonnel.responsabilites }}
              </p>
            </div>

            <div v-if="activePersonnel.entreprise">
              <span class="font-medium text-gray-500 block mb-1">Entreprise / Organisme externe :</span>
              <p class="text-gray-800 font-medium">
                {{ activePersonnel.entreprise }}
              </p>
            </div>

            <div v-if="activePersonnel.siteUniv || activePersonnel.sitePerso" class="pt-2 flex gap-2">
              <a
                v-if="activePersonnel.siteUniv"
                :href="activePersonnel.siteUniv"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors"
              >
                <i class="pi pi-external-link text-[10px]"></i>
                <span>Site URCA</span>
              </a>
              <a
                v-if="activePersonnel.sitePerso"
                :href="activePersonnel.sitePerso"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors"
              >
                <i class="pi pi-globe text-[10px]"></i>
                <span>Site personnel</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2 pt-2">
        <Button
          label="Fermer"
          icon="pi pi-times"
          severity="secondary"
          size="small"
          outlined
          @click="isModalVisible = false"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped></style>
