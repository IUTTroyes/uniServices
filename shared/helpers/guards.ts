import type { Router } from 'vue-router'
import { useSecurityStore } from '@stores/security/securityStore'

export function registerSecurityGuard(router: Router): void {
  router.beforeEach(async (to) => {
    const security = useSecurityStore()

    await security.loadSecurityContext()

    const packageCode = to.meta.package as string | undefined
    const permission = to.meta.permission as string | undefined

    if (packageCode && !security.hasPackage(packageCode)) {
      return '/access'; 
    }

    if (permission && !security.hasPermission(permission)) {
      return '/access';
    }

    return true
  })
}
