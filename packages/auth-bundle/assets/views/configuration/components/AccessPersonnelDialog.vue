<script setup>
import { computed } from 'vue'

const props = defineProps({
  isVisible: Boolean,
  personnel: Object,
  permissionCatalog: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:isVisible', 'save'])

const packageNames = computed(() => Object.keys(props.permissionCatalog || {}))
const departments = computed(() => props.personnel?.departements ?? [])

const hasPackage = (department, packageName) => (department?.packages ?? []).includes(packageName)
const hasPermission = (department, permissionName) => (department?.permissions ?? []).includes(permissionName)

const togglePackage = (department, packageName, enabled) => {
  const packages = new Set(department.packages ?? [])
  if (enabled) packages.add(packageName)
  else packages.delete(packageName)
  department.packages = [...packages]
}

const togglePermission = (department, permissionName, enabled) => {
  const permissions = new Set(department.permissions ?? [])
  if (enabled) permissions.add(permissionName)
  else permissions.delete(permissionName)
  department.permissions = [...permissions]
}

const closeDialog = () => emit('update:isVisible', false)
const save = () => emit('save')
</script>

<template>
  <Dialog
    :visible="props.isVisible"
    :modal="true"
    :closable="true"
    :draggable="false"
    :style="{ width: '92vw', maxWidth: '1140px' }"
    :breakpoints="{ '1199px': '95vw', '575px': '98vw' }"
    header="Droits et affectations"
    @update:visible="closeDialog"
  >
    <div v-if="!props.personnel" class="text-center py-5 text-muted-color">
      Aucun personnel sélectionné.
    </div>

    <div v-else class="flex flex-col gap-4 card">
      <div class="card-header">
        <div>
          <p class="m-0 text-xs font-bold uppercase tracking-wider text-muted-color">Gestion des accès</p>
          <h2 class="my-1 text-2xl font-semibold">{{ props.personnel.prenom }} {{ props.personnel.nom }}</h2>
          <p class="m-0 text-xs font-semibold uppercase text-muted-color">{{ props.personnel.numeroHarpege }}</p>
        </div>
      </div>

      <Message v-if="departments.length === 0" severity="warn" class="mb-4" icon="pi pi-exclamation-triangle">
        Aucun département affecté.
      </Message>

      <div v-else class="card-body flex flex-col gap-4">
        <section v-for="department in departments" :key="department.id ?? department.departementId">
          <div class="mb-3 flex items-start justify-between gap-4">
            <div>
              <p class="m-0! text-xs font-bold uppercase text-muted-color">Département</p>
              <h4 class="m-0! text-lg font-semibold">{{ department.libelle }}</h4>
            </div>

            <div class="flex items-center gap-2 rounded-full border border-surface-300 px-3 py-1.5">
              <label class="text-sm font-medium" :for="`default-${department.id ?? department.departementId}`">Défaut</label>
              <Checkbox
                :id="`default-${department.id ?? department.departementId}`"
                v-model="department.defaut"
                :binary="true"
              />
            </div>
          </div>

          <template class="flex flex-col gap-8">
            <Fieldset>
              <template #legend>
                <div class="flex items-center pl-2">
                  <i class="pi pi-box bg-primary-400/20 rounded-full p-4 text-primary-500"/>
                  <div class="flex flex-col">
                    <span class="font-bold px-2 capitalize">Packages actifs</span>
                    <em class="text-muted-color px-2">Accès aux applications</em>
                  </div>
                </div>
              </template>
              <div class="flex flex-wrap gap-3">
                <div v-for="pkg in packageNames" :key="`${department.id ?? department.departementId}-${pkg}`" class="inline-flex items-center gap-2 rounded-full border border-surface-300 bg-surface-0 px-3 py-1.5">
                  <Checkbox
                      :input-id="`package-${department.id ?? department.departementId}-${pkg}`"
                      :model-value="hasPackage(department, pkg)"
                      :binary="true"
                      @update:model-value="togglePackage(department, pkg, $event)"
                  />
                  <label :for="`package-${department.id ?? department.departementId}-${pkg}`">{{ pkg }}</label>
                </div>
              </div>
            </Fieldset>

            <Fieldset legend="Rôles">
              <template #legend>
                <div class="flex items-center pl-2">
                  <i class="pi pi-key bg-primary-400/20 rounded-full p-4 text-primary-500"/>
                  <div class="flex flex-col">
                    <span class="font-bold px-2 capitalize">Rôles</span>
                    <em class="text-muted-color px-2">Affectation des rôles par département</em>
                  </div>
                </div>
              </template>
              <div v-if="Object.keys(props.permissionCatalog || {}).length === 0" class="text-sm text-muted-color">
                Aucun catalogue de rôles disponible.
              </div>
              <div v-else>
                <div v-for="pkg in packageNames" :key="pkg" class="mt-3 first:mt-0">
                  <p class="mb-2 text-xs font-bold uppercase text-muted-color">{{ pkg }}</p>
                  <div class="flex flex-wrap gap-3">
                    <div v-for="role in (props.permissionCatalog[pkg] ?? [])" :key="`${department.id ?? department.departementId}-${role.role}`" class="inline-flex items-center gap-2 rounded-full border border-surface-300 bg-surface-0 px-3 py-1.5">
                      <Checkbox
                          :input-id="`permission-${department.id ?? department.departementId}-${role.role}`"
                          :model-value="hasPermission(department, role.role)"
                          :binary="true"
                          @update:model-value="togglePermission(department, role.role, $event)"
                      />
                      <label :for="`permission-${department.id ?? department.departementId}-${role.role}`">{{ role.label || role.role }}</label>
                    </div>
                  </div>
                </div>
              </div>
            </Fieldset>
          </template>
          <Divider/>
        </section>
      </div>

      <div class="mt-2 flex justify-end gap-2 border-t border-surface-300 pt-4">
        <Button label="Annuler" severity="secondary" text @click="closeDialog" />
        <Button label="Enregistrer" icon="pi pi-save" @click="save" />
      </div>
    </div>
  </Dialog>
</template>
