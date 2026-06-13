// ----------------------------------------------------------------------------
// Internal imports (grouped by category)
// ----------------------------------------------------------------------------

// String & Date utilities
import { formatAdresse } from './adresse.js'
import { formatDateCourt, formatDateLong, jourDate, heuresMinutesDate, getISOWeekNumber } from './date.js'
import { capitalize } from './string.js'

// Color utilities
import {
    adjustColor,
    rgbToHsl,
    hslToRgb,
    colorNameToRgb,
    hexToRgb,
    darkenColor
} from './colors.js'

// API & Authentication
import apiCall from './apiCall.js'
import api from './axios.js'
import { isAuthenticated, logout, clearUserCache, getAuthenticatedUser } from './authService.js'

// UI Components & Utilities
import { showInfo, showDanger, showWarn, showSuccess } from './toast.js'
import { debounce } from './debounce.js'
import { exportCsv } from './downloadCsv.js'

// Version & Branding
import { useVersion } from './useVersion.js'
import { resolveLogoEtablissementUrl } from './logo.js'

// ----------------------------------------------------------------------------
// Re-exports: Grouped by functionality
// ----------------------------------------------------------------------------

// Formatting utilities
export {
    formatAdresse,
    capitalize,
    formatDateCourt,
    formatDateLong,
    jourDate,
    heuresMinutesDate,
    getISOWeekNumber
}

// Color utilities
export {
    rgbToHsl,
    hslToRgb,
    hexToRgb,
    darkenColor,
    adjustColor,
    colorNameToRgb
}

// API & Authentication
export {
    apiCall,
    api,
    isAuthenticated,
    logout,
    clearUserCache,
    getAuthenticatedUser
}

// UI utilities
export {
    debounce,
    exportCsv,
    showInfo,
    showDanger,
    showWarn,
    showSuccess
}

// Version & Branding
export {
    useVersion,
    resolveLogoEtablissementUrl
}
