import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getSecurityContextService } from '@requests';

export const useSecurity = defineStore('security', () => {
  const user = ref<any>(null);
  const currentDepartment = ref<any>(null);
  const departments = ref<any[]>([]);
  const activePackages = ref<string[]>([]);
  const resolvedPermissions = ref<string[]>([]);
  const scopedPermissions = ref<Record<string, { diplomeIds: number[] }>>({});
  const isLoaded = ref(false);
  const isLoading = ref(false);

  const loadSecurityContext = async () => {
    if (isLoading.value) return;
    isLoading.value = true;
    try {
      const response = await getSecurityContextService();
      if (response) {
        user.value = response.user || null;
        currentDepartment.value = response.currentDepartment || null;
        departments.value = response.departments || [];
        activePackages.value = response.packages || [];
        resolvedPermissions.value = response.permissions || [];
        scopedPermissions.value = response.scopedPermissions || {};
        isLoaded.value = true;
      }
    } catch (e) {
      console.error('Failed to load security context:', e);
    } finally {
      isLoading.value = false;
    }
  };

  const hasPermission = (permission: string | boolean): boolean => {
    if (permission === true) return true;
    if (permission === false) return false;

    // SuperAdmin override
    if (resolvedPermissions.value.includes('SUPER_ADMIN')) {
      return true;
    }

    if (!isLoaded.value) return false;
    return resolvedPermissions.value.includes(permission as string);
  };

  const hasPackage = (packageName: string): boolean => {
    // SuperAdmin override or core
    if (resolvedPermissions.value.includes('SUPER_ADMIN')) {
      return true;
    }

    if (packageName === 'auth') {
      return true;
    }
    if (!isLoaded.value) return false;
    const aliases: Record<string, string> = { stages: 'stage', document: 'documents' };
    const normalized = aliases[packageName] || packageName;
    return activePackages.value.some(name => (aliases[name] || name) === normalized);
  };

  const reset = () => {
    user.value = null;
    currentDepartment.value = null;
    departments.value = [];
    activePackages.value = [];
    resolvedPermissions.value = [];
    scopedPermissions.value = {};
    isLoaded.value = false;
  };

  return {
    user,
    currentDepartment,
    departments,
    activePackages,
    resolvedPermissions,
    scopedPermissions,
    isLoaded,
    isLoading,
    loadSecurityContext,
    hasPermission,
    hasPackage,
    reset,
  };
});
