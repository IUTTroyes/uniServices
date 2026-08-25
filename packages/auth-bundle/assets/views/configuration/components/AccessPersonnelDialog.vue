<script setup>
import { computed } from 'vue'
import { tools } from '@config/uniServices.js'

const props = defineProps({
  isVisible: Boolean,
  personnel: Object,
  permissionCatalog: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:isVisible', 'save'])

const packageNames = computed(() => {
  const realPackages = (tools || [])
      .map((bundle) => bundle?.urlSlug)
      .filter(Boolean)

  const fromDepartments = (props.personnel?.departements ?? [])
      .flatMap((department) => department?.packages ?? [])
      .filter(Boolean)

  return [...new Set([...realPackages, ...fromDepartments])]
})

const permissionPackageNames = computed(() => Object.keys(props.permissionCatalog || {}))
const departments = computed(() => props.personnel?.departements ?? [])
const SUPER_ADMIN_ROLE = 'SUPER_ADMIN'
const isPersonnelSuperAdmin = computed(() =>
  departments.value.some((department) => (department?.permissions ?? []).includes(SUPER_ADMIN_ROLE))
)

const departmentKey = (department) => String(department.id ?? department.departementId)

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

const becomeSuperAdminAllDepartments = () => {
  departments.value.forEach((department) => {
    const permissions = new Set(department.permissions ?? [])
    permissions.add(SUPER_ADMIN_ROLE)
    department.permissions = [...permissions]
  })
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
      <div class="card-header flex flex-row justify-between items-center">
        <div>
          <p class="m-0 text-xs font-bold uppercase tracking-wider text-muted-color">Gestion des accès</p>
          <h2 class="my-1 text-2xl font-semibold"><i v-if="isPersonnelSuperAdmin" class="pi pi-crown text-yellow-500 bg-yellow-500/10 border-2 p-2 rounded-full font-bold! mr-2" />{{ props.personnel.prenom }} {{ props.personnel.nom }}</h2>
          <p class="m-0 text-xs font-semibold uppercase text-muted-color">{{ props.personnel.numeroHarpege }}</p>
        </div>
        <Button
            label="Déclarer SUPER_ADMIN"
            icon="pi pi-shield"
            severity="primary"
            size="small"
            :disabled="departments.length === 0"
            @click="becomeSuperAdminAllDepartments"
        />
      </div>

      <Message v-if="departments.length === 0" severity="warn" class="mb-4" icon="pi pi-exclamation-triangle">
        Aucun département affecté.
      </Message>

      <div v-else class="card-body">
        <Tabs :value="departmentKey(departments[0])">
          <TabList>
            <Tab
                v-for="department in departments"
                :key="departmentKey(department)"
                :value="departmentKey(department)"
            >
              {{ department.libelle }}
            </Tab>
          </TabList>

          <TabPanels>
            <TabPanel
                v-for="department in departments"
                :key="departmentKey(department)"
                :value="departmentKey(department)"
            >
              <div class="flex flex-col gap-4">
                <div class="mb-1 flex items-start justify-between gap-4">
                  <div>
                    <p class="m-0! text-xs font-bold uppercase text-muted-color">Département</p>
                    <h4 class="m-0! text-lg font-semibold">{{ department.libelle }}</h4>
                  </div>

                  <div class="flex items-center gap-2 rounded-full border border-surface-300 px-3 py-1.5">
                    <label class="text-sm font-medium" :for="`default-${departmentKey(department)}`">Défaut</label>
                    <Checkbox
                        :id="`default-${departmentKey(department)}`"
                        v-model="department.defaut"
                        :binary="true"
                    />
                  </div>
                </div>

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
                    <div
                        v-for="pkg in packageNames"
                        :key="`${departmentKey(department)}-${pkg}`"
                        class="inline-flex items-center gap-2 rounded-full border border-surface-300 bg-surface-0 px-3 py-1.5"
                    >
                      <Checkbox
                          :input-id="`package-${departmentKey(department)}-${pkg}`"
                          :model-value="hasPackage(department, pkg)"
                          :binary="true"
                          @update:model-value="togglePackage(department, pkg, $event)"
                      />
                      <label :for="`package-${departmentKey(department)}-${pkg}`">{{ pkg }}</label>
                    </div>
                  </div>
                </Fieldset>

                <Fieldset>
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
                    <div v-for="pkg in permissionPackageNames" :key="pkg" class="mt-3 first:mt-0">
                      <p class="mb-2 text-xs font-bold uppercase text-muted-color">{{ pkg }}</p>
                      <div class="flex flex-wrap gap-3">
                        <div
                            v-for="role in (props.permissionCatalog[pkg] ?? [])"
                            :key="`${departmentKey(department)}-${role.role}`"
                            class="inline-flex items-center gap-2 rounded-full border border-surface-300 bg-surface-0 px-3 py-1.5"
                        >
                          <Checkbox
                              :input-id="`permission-${departmentKey(department)}-${role.role}`"
                              :model-value="hasPermission(department, role.role)"
                              :binary="true"
                              @update:model-value="togglePermission(department, role.role, $event)"
                          />
                          <label :for="`permission-${departmentKey(department)}-${role.role}`">{{ role.label || role.role }}</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </Fieldset>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>

      <div class="mt-2 flex flex-wrap justify-end gap-2 border-t border-surface-300 pt-4">
        <Button label="Annuler" severity="secondary" text @click="closeDialog" />
        <Button label="Enregistrer" icon="pi pi-save" @click="save" />
      </div>
    </div>
  </Dialog>
</template>
