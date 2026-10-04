# Système de configuration des intégrations établissement

## Objectif

Le système permet de piloter, par établissement, l’activation et la configuration des intégrations externes (ex: Edusign, OréBut) sans codage en dur.

## Modèle de données

La configuration est stockée dans `Etablissement.settings` (colonne JSON).

Structure attendue :

```json
{
  "integrations": {
    "edusign": {
      "enabled": false,
      "scope": [],
      "apiKey": null,
      "apiUrl": null
    },
    "orebut": {
      "enabled": false,
      "apiKey": null,
      "apiUrl": null
    }
  }
}
```

## Validation et normalisation (Back)

- Fichier principal : `back/src/Entity/Etablissement.php`
- Le champ `settings` est exposé dans les groupes API : `etablissement:read`, `etablissement:write`.
- `getSettings()` et `setSettings()` passent par une normalisation via `OptionsResolver` :
  - valeurs par défaut garanties (`enabled=false`, `scope=[]`, `apiKey=null`, `apiUrl=null`),
  - types contrôlés (`bool`, `array`, `string|null`).
- Toute configuration incomplète est automatiquement complétée avec les defaults.

## Service d’accès métier

- Fichier : `back/src/Service/Etablissement/EtablissementSettingsService.php`
- Rôle : centraliser l’accès au paramétrage, éviter la lecture directe du JSON dans les règles métier.
- Méthodes disponibles :
  - `isEdusignEnabled()`
  - `getEdusignScope()`
  - `getEdusignApiKey()`
  - `getEdusignApiUrl()`
  - `isOrebutEnabled()`
  - `getOrebutApiKey()`
  - `getOrebutApiUrl()`

## Interface de configuration (Front)

### Intégrations

- Vue dédiée : `packages/auth-bundle/assets/views/configuration/IntegrationsView.vue`
- Route : `/auth/configuration/integrations`
- Menu : section établissement dans `ConfigurationView.vue`.
- Champs gérés :
  - Edusign : activation, périmètre `FI/FC`, URL API, clé API.
  - OréBut : activation, URL API, clé API.
- La vue normalise toujours les données avant sauvegarde pour garantir une structure cohérente.

## API et persistance

- Ressource API : `Etablissement` (Api Platform)
- Mise à jour : `PATCH /api/etablissements/{id}` (`application/merge-patch+json`)
- Exécution de la persistance : `back/src/State/Processor/EtablissementProcessor.php`

## Recommandations d’usage

- Ne pas accéder directement au JSON `settings` depuis les fonctionnalités métier : passer par `EtablissementSettingsService`.
- Pour une nouvelle intégration :
  1. ajouter la structure dans les defaults + normalisation,
  2. exposer des getters dédiés dans le service,
  3. ajouter/adapter la partie UI de la vue Intégrations,
  4. documenter la nouvelle clé dans ce document.
