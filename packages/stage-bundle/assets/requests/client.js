/** Outils communs aux services de requêtes du package Stages. */

/** En-têtes des écritures API Platform (création JSON-LD, modification partielle). */
export const JSON_LD = { headers: { 'Content-Type': 'application/ld+json' } };
export const MERGE_PATCH = { headers: { 'Content-Type': 'application/merge-patch+json' } };

/** Éléments d'une collection, quel que soit le format de réponse (tableau JSON, JSON-LD, Hydra). */
export const elements = (data) => (Array.isArray(data) ? data : (data?.member || data?.['hydra:member'] || []));
