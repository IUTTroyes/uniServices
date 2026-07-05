import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getSecurityContextService } from '@requests';

export const useSecurity = defineStore('security', () => {
  const user = ref<any>(null);
  const currentDepartment = ref<any>(null);
  const departments = ref<any[]>([]);
  const activePackages = ref<string[]>([]);
  const resolvedPermissions = ref<string[]>([]);
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
    if (user.value?.roles?.includes('ROLE_SUPER_ADMIN')) {
      return true;
    }

    if (!isLoaded.value) return false;
    return resolvedPermissions.value.includes(permission as string);
  };

  const hasPackage = (packageName: string): boolean => {
    // SuperAdmin override or core
    if (user.value?.roles?.includes('ROLE_SUPER_ADMIN')) {
      return true;
    }

    const normalizedName = packageName === 'intranet' ? 'core' : packageName;

    if (normalizedName === 'core' || normalizedName === 'auth') {
      return true;
    }
    if (!isLoaded.value) return false;
    return activePackages.value.includes(normalizedName);
  };

  const reset = () => {
    user.value = null;
    currentDepartment.value = null;
    departments.value = [];
    activePackages.value = [];
    resolvedPermissions.value = [];
    isLoaded.value = false;
  };

  return {
    user,
    currentDepartment,
    departments,
    activePackages,
    resolvedPermissions,
    isLoaded,
    isLoading,
    loadSecurityContext,
    hasPermission,
    hasPackage,
    reset,
  };
});
