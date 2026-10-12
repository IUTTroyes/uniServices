<script setup>
import {onMounted, ref} from "vue";
import {useEtablissementStore} from "@stores";
import {ValidatedInput, ErrorView, ListSkeleton} from "@components";
import {updateEtablissementService} from "@requests";

const etablissementStore = useEtablissementStore();
const etablissement = ref();
const isLoadingEtablissement = ref(true);
const hasError = ref(false);

const defaultSettings = {
  integrations: {
    edusign: {
      enabled: false,
      scope: [],
      apiKey: null,
      apiUrl: null,
    },
    orebut: {
      enabled: false,
      apiUrl: null,
    },
  },
};

const ensureSettingsObject = (settings) => {
  const integrations = settings?.integrations ?? {};
  const edusign = integrations.edusign ?? {};
  const orebut = integrations.orebut ?? {};

  return {
    integrations: {
      edusign: {
        enabled: !!edusign.enabled,
        scope: Array.isArray(edusign.scope) ? edusign.scope.filter(scope => ['FI', 'FC'].includes(scope)) : [],
        apiKey: edusign.apiKey ?? null,
        apiUrl: edusign.apiUrl ?? null,
      },
      orebut: {
        enabled: !!orebut.enabled,
        apiKey: orebut.apiKey ?? null,
        apiUrl: orebut.apiUrl ?? null,
      }
    }
  };
};

const extractEtablissement = (payload) => {
  if (!payload) {
    return null;
  }

  if (payload.id) {
    return payload;
  }

  const candidates = payload.items ?? payload.member ?? payload['hydra:member'] ?? [];
  if (Array.isArray(candidates) && candidates.length > 0) {
    return candidates[0];
  }

  return null;
};

onMounted(async () => {
  try {
    await etablissementStore.getEtablissement();
    etablissement.value = extractEtablissement(etablissementStore.etablissement);
    if (!etablissement.value) {
      hasError.value = true;
      return;
    }
    etablissement.value.settings = ensureSettingsObject(etablissement.value?.settings ?? defaultSettings);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching etablissement integrations:', error);
  } finally {
    isLoadingEtablissement.value = false;
  }
});

const updateIntegrations = async () => {
  try {
    etablissement.value.settings = ensureSettingsObject(etablissement.value?.settings ?? defaultSettings);

    await updateEtablissementService(etablissement.value.id, {
      settings: etablissement.value.settings
    }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating etablissement integrations:', error);
  } finally {
    isLoadingEtablissement.value = false;
  }
};

const toggleEdusignScope = (scope) => {
  const currentScope = etablissement.value.settings.integrations.edusign.scope;

  if (currentScope.includes(scope)) {
    etablissement.value.settings.integrations.edusign.scope = currentScope.filter(item => item !== scope);
    return;
  }

  etablissement.value.settings.integrations.edusign.scope = [...currentScope, scope];
};
</script>

<template>
  <div class="card">
    <div class="card-header">
    <h2 class="text-2xl! mb-0! font-bold">Intégrations</h2>
    <em>Gestion des intégrations externes de l'établissement</em>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>
      <div class="card-body">
        <ListSkeleton v-if="isLoadingEtablissement"/>
        <div v-else>
          <form @submit.prevent="updateIntegrations()" class="flex flex-col gap-4">
            <div class="mb-3">
              <label class="font-medium">Activer Edusign</label>
              <div>
                <InputSwitch v-model="etablissement.settings.integrations.edusign.enabled"/>
              </div>
            </div>

            <div v-if="etablissement.settings.integrations.edusign.enabled" class="flex flex-col gap-3 mb-3">
              <div class="flex gap-4 items-center">
                <label class="font-medium">Utiliser pour :</label>
                <div class="flex gap-3 items-center">
                  <div class="flex items-center gap-2">
                    <Checkbox
                        :modelValue="etablissement.settings.integrations.edusign.scope.includes('FI')"
                        :binary="true"
                        @change="toggleEdusignScope('FI')"
                    />
                    <span>FI</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox
                        :modelValue="etablissement.settings.integrations.edusign.scope.includes('FC')"
                        :binary="true"
                        @change="toggleEdusignScope('FC')"
                    />
                    <span>FC</span>
                  </div>
                </div>
              </div>

              <ValidatedInput
                  v-model="etablissement.settings.integrations.edusign.apiUrl"
                  name="edusign_api_url"
                  label="URL API Edusign"
                  type="text"
                  :rules="[]"
                  class="w-full"
              />

              <ValidatedInput
                  v-model="etablissement.settings.integrations.edusign.apiKey"
                  name="edusign_api_key"
                  label="Clé API Edusign"
                  type="text"
                  :rules="[]"
                  class="w-full"
              />
            </div>

            <div class="mb-3">
              <label class="font-medium">Activer OréBut</label>
              <div>
                <InputSwitch v-model="etablissement.settings.integrations.orebut.enabled"/>
              </div>
            </div>

            <div v-if="etablissement.settings.integrations.orebut.enabled" class="flex flex-col gap-3 mb-3">
              <ValidatedInput
                  v-model="etablissement.settings.integrations.orebut.apiUrl"
                  name="orebut_api_url"
                  label="URL API OréBut"
                  type="text"
                  :rules="[]"
                  class="w-full"
              />
            </div>

            <Button label="Enregistrer" class="w-full" type="submit"/>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>
