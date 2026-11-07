// ----------------------------------------------------------------------------
// Internal imports (grouped by category)
// ----------------------------------------------------------------------------

// String & Date utilities
import { formatAdresse } from './adresse.js'
import { formatDateCourt, formatDateLong, formatDateFr, STYLES_DATE, jourDate, heuresMinutesDate, getISOWeekNumber, parseApiDate } from './date.js'
import { capitalize } from './string.js'

// Color utilities
import {
    adjustColor,
    couleurCompetence,
    texteSurCompetence,
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
    formatDateFr,
    STYLES_DATE,
    jourDate,
    heuresMinutesDate,
    getISOWeekNumber,
    parseApiDate
}

// Color utilities
export {
    rgbToHsl,
    hslToRgb,
    hexToRgb,
    darkenColor,
    adjustColor,
    couleurCompetence,
    texteSurCompetence,
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
