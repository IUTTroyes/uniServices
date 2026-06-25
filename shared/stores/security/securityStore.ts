import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface SecurityDepartmentContext {
  id: number
  code: string
  label: string
  packages: string[]
  permissions: string[]
}

export interface SecurityContext {
  user: {
    id: number
    displayName: string
    type: 'personnel' | 'student'
  }
  currentDepartment: string | null
  departments: SecurityDepartmentContext[]
}

export const useSecurityStore = defineStore('security', () => {
  const context = ref<SecurityContext | null>(null)
  const loading = ref(false)

  const currentDepartment = computed(() => {
    if (!context.value?.currentDepartment) {
      return null
    }

    return context.value.departments.find(
      department => department.code === context.value?.currentDepartment,
    ) ?? null
  })

  function hasPackage(packageCode: string): boolean {
    return currentDepartment.value?.packages.includes(packageCode) ?? false
  }

  function hasPermission(permission: string): boolean {
    return currentDepartment.value?.permissions.includes(permission) ?? false
  }

  async function loadSecurityContext(): Promise<void> {
    if (context.value || loading.value) {
      return
    }

    loading.value = true

    try {
      const response = await fetch('/api/me/security-context')

      if (!response.ok) {
        throw new Error('Impossible de charger le contexte de sécurité')
      }

      context.value = await response.json()
    } finally {
      loading.value = false
    }
  }

  function setCurrentDepartment(code: string): void {
    if (!context.value) {
      return
    }

    context.value.currentDepartment = code
  }

  return {
    context,
    loading,
    currentDepartment,
    hasPackage,
    hasPermission,
    loadSecurityContext,
    setCurrentDepartment,
  }
})
