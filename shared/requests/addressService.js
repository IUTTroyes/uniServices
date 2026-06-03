import axios from 'axios';

/**
 * Service pour l'autocomplétion des adresses via Géoplateforme (IGN)
 * API officielle française pour la recherche et le géocodage d'adresses
 * Les requêtes sont proxy-ées par le backend pour éviter les problèmes CORS
 * Documentation: https://geoplateforme.gouv.fr/
 */

// API locale (proxy vers Géoplateforme)
const API_BASE_URL = '/api/address';

/**
 * Recherche des adresses basée sur une requête de texte via Géoplateforme (IGN)
 * @param {string} query - La requête de recherche
 * @param {string} countryCode - Code du pays (ex: 'fr' pour France) - utilisé pour le contexte
 * @returns {Promise<Array>} Liste des adresses trouvées
 */
const searchAddresses = async (query, countryCode = 'fr') => {
  if (!query || query.trim().length < 3) {
    return [];
  }

  try {
    // Appel à notre endpoint API local qui proxy vers Géoplateforme
    const response = await axios.get(`${API_BASE_URL}/search`, {
      params: {
        q: query,  // Paramètre officiel Géoplateforme
      },
    });

    // Géoplateforme retourne un objet avec une propriété "features"
    const features = response.data.features || [];

    return features.map(feature => {
      const props = feature.properties || {};
      const geometry = feature.geometry || {};

      // Extraire les coordonnées (GeoJSON format)
      const coords = geometry.coordinates || [0, 0];
      const lon = coords[0];
      const lat = coords[1];

      // Construire l'adresse complète
      const fullAddress = props.label || '';

      // Parser l'adresse pour extraire les composants
      const addressParts = fullAddress.split(',').map(p => p.trim());
      const streetAddress = addressParts[0] || '';

      // Extraire le code postal et la ville
      let postalCode = props.postalCode || '';
      let city = props.city || props.town || '';

      // Chercher le code postal et la ville dans les parties de l'adresse
      for (const part of addressParts) {
        if (/^\d{5}$/.test(part)) {
          postalCode = part;
        } else if (!city && part !== streetAddress) {
          city = part;
        }
      }

      return {
        label: fullAddress,
        value: fullAddress,
        address: streetAddress,
        postalCode: postalCode,
        city: city,
        country: 'France',
        lat: lat.toString(),
        lon: lon.toString(),
        raw: feature,
      };
    });
  } catch (error) {
    console.error('Erreur lors de la recherche d\'adresses via l\'API locale:', error);
    return [];
  }
};

/**
 * Obtient les détails d'une adresse sur la base de coordonnées (géocodage inversé) via Géoplateforme
 * @param {number} lat - Latitude
 * @param {number} lon - Longitude
 * @returns {Promise<Object>} Détails de l'adresse
 */
const reverseGeocode = async (lat, lon) => {
  try {
    // Appel à notre endpoint API local qui proxy vers Géoplateforme
    const response = await axios.get(`${API_BASE_URL}/reverse`, {
      params: {
        lon,
        lat,
      },
    });

    const features = response.data.features || [];
    if (features.length === 0) {
      return null;
    }

    const feature = features[0];
    const props = feature.properties || {};
    const fullAddress = props.label || '';

    // Parser l'adresse pour extraire les composants
    const addressParts = fullAddress.split(',').map(p => p.trim());
    const streetAddress = addressParts[0] || '';

    let postalCode = props.postalCode || '';
    let city = props.city || props.town || '';

    // Chercher le code postal et la ville dans les parties de l'adresse
    for (const part of addressParts) {
      if (/^\d{5}$/.test(part)) {
        postalCode = part;
      } else if (!city && part !== streetAddress) {
        city = part;
      }
    }

    return {
      label: fullAddress,
      value: fullAddress,
      address: streetAddress,
      postalCode: postalCode,
      city: city,
      country: 'France',
      lat: lat.toString(),
      lon: lon.toString(),
      raw: feature,
    };
  } catch (error) {
    console.error('Erreur lors du géocodage inversé via l\'API locale:', error);
    return null;
  }
};

export { searchAddresses, reverseGeocode };



