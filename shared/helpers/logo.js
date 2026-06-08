export function resolveLogoEtablissementUrl(logoName) {
    if (!logoName) {
        return LogoIut;
    }

    const apiBaseUrl = import.meta.env.VITE_BASE_URL;
    let baseUrl = window.location.origin;

    if (apiBaseUrl) {
        baseUrl = apiBaseUrl;
    } else if (window.location.port === '3000') {
        baseUrl = `${window.location.protocol}//${window.location.hostname}:8000`;
    }

    try {
        return new URL(`/uploads/etablissement/${logoName}`, baseUrl).toString();
    } catch (error) {
        console.error('Erreur lors de la construction de l\'URL du logo:', error);
    }
}
