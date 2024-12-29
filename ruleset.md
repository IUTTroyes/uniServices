# Guide suprême de l'Itranet V4

## Backend - SF7

- 
- 

## Frontend - VueJs3

- L'ordre doit être script, puis template et styles dans un fichier .vue
- Les composants doivent être en PascalCase
- Les méthodes doivent être en camelCase
- Les requêtes API doivent être dans un fichier js à part dans le dossier ``common-requests/`` et sont ensuite exploiter dans les stores si nécessaire ``common-stores/``
- Utiliser l'intercepteur axios pour chaque requête pour tester le token et exploiter la variable d'environnement qui contient l'URL du backoffice
	```javascript
	import api from '@/axios';
	api.get(`/api/hello`)
	```
- Utiliser les *Skeleton* de primevue comme loader -> en faire des composants si nécessaire

### Alias

#### src

Dans chaque projet front, l'alias `@` est utilisé pour accéder au dossier `src` du projet.

#### Packages

Pour accéder aux packages communs utiliser les alias suivants :

- @components : pour les composants communs (ex: bouton, input, etc) dans le dossier ``common-components/``
- @styles : pour les styles communs (ex: variables, mixins, etc) dans le dossier ``common-styles/``
- @helpers : pour les fonctions communes (ex: formatage de date, etc) dans le dossier ``common-helpers/``
- @stores : pour les stores communs (ex: auth, etc) dans le dossier ``common-stores/``
- @requests : pour les requêtes API communes (ex: matières, diplômes, etc) dans le dossier ``common-requests/``

