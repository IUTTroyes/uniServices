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

const flattenUnique = (values = []) => [...new Set((Array.isArray(values) ? values : []).filter(Boolean))]
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
const save = () => {
  emit('save')
}
</script>

<template>
  <Dialog
      :visible="props.isVisible"
      :modal="true"
      :closable="true"
      :draggable="false"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
      header="Droits et affectations"
      @update:visible="closeDialog"
  >
    <div v-if="!props.personnel" class="text-center py-5 text-muted-color">
      Aucun personnel sélectionné.
    </div>

    <div v-else class="dialog">
      <div class="flex flex-col md:flex-row justify-between items-start w-full">
        <div>
          <div class="flex flex-col items-start">
            <h2 class="m-0!">{{ props.personnel.prenom }} {{ props.personnel.nom }}</h2>
            <p class="uppercase text-xs font-bold mb-0! text-muted-color">
              {{ props.personnel.numeroHarpege }}
            </p>
          </div>
        </div>
      </div>
      <Divider></Divider>
      <Message v-if="departments.length === 0" severity="warn" class="mb-4" icon="pi pi-exclamation-triangle">
        Aucun département affecté.
      </Message>
      <div v-else class="">
        <div v-for="department in departments" :key="department.id ?? department.departementId">
          <div class="">
            <div class="flex justify-content-between align-items-center mb-3">
              <div>
                <p class="text-xs uppercase text-muted-color mb-1">Département</p>
                <h4 class="m-0">{{ department.libelle }}</h4>
              </div>
              <div class="flex align-items-center gap-2">
                <label class="text-sm font-medium" :for="`default-${department.id ?? department.departementId}`">Défaut</label>
                <Checkbox
                    :id="`default-${department.id ?? department.departementId}`"
                    v-model="department.defaut"
                    :binary="true"
                />
              </div>
            </div>

            <div class="mb-4">
              <p class="font-semibold mb-2">Packages actifs</p>
              <div class="flex flex-wrap gap-3">
                <div v-for="pkg in packageNames" :key="`${department.id ?? department.departementId}-${pkg}`" class="package-item">
                  <Checkbox
                      :input-id="`package-${department.id ?? department.departementId}-${pkg}`"
                      :model-value="hasPackage(department, pkg)"
                      :binary="true"
                      @update:model-value="togglePackage(department, pkg, $event)"
                  />
                  <label :for="`package-${department.id ?? department.departementId}-${pkg}`">{{ pkg }}</label>
                </div>
              </div>
            </div>

            <div>
              <p class="font-semibold mb-2">Permissions</p>
              <div v-if="Object.keys(props.permissionCatalog || {}).length === 0" class="text-sm text-muted-color">
                Aucun catalogue de permissions disponible.
              </div>
              <div v-else class="permission-list">
                <div v-for="pkg in packageNames" :key="pkg" class="mb-3">
                  <p class="text-xs uppercase text-muted-color mb-2">{{ pkg }}</p>
                  <div class="flex flex-wrap gap-3">
                    <div v-for="role in (props.permissionCatalog[pkg] ?? [])" :key="`${department.id ?? department.departementId}-${role.role}`" class="permission-item">
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
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-content-end gap-2 mt-4 pt-3 border-top-1">
        <Button label="Annuler" severity="secondary" text @click="closeDialog" />
        <Button label="Enregistrer" icon="pi pi-save" @click="save" />
      </div>
    </div>
  </Dialog>
</template>

<style scoped>
</style>
