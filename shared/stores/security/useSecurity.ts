import { useSecurityStore } from './securityStore'

export function useSecurity() {
  const security = useSecurityStore()

  return {
    context: security.context,
    currentDepartment: security.currentDepartment,
    hasPackage: security.hasPackage,
    hasPermission: security.hasPermission,
    loadSecurityContext: security.loadSecurityContext,
  }
}