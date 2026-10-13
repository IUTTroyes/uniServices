// ----------------------------------------------------------------------------
// Internal imports (grouped by category)
// ----------------------------------------------------------------------------

// Permissions utilities
import { hasPermission, permissionDirective, registerPermissionDirective } from './permissions';

// Status utilities
import { getStatutColor, getStatutText } from './statuts';

// Application utilities (manifest colors & badges)
import {
  APPLICATION_REGISTRY,
  normalizeAppSlug,
  getApplicationMeta,
  getAppTagInfo,
} from './applicationColors';

// Permissions
export {
  hasPermission,
  permissionDirective,
  registerPermissionDirective,
};

// Status
export {
  getStatutColor,
  getStatutText,
};

// Applications
export {
  APPLICATION_REGISTRY,
  normalizeAppSlug,
  getApplicationMeta,
  getAppTagInfo,
};


