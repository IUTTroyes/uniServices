# Guide suprême de l'Itranet V4

## Backend - SF7

- 
- 

## Frontend - VueJs3

- Utiliser l'intercepteur axios pour chaque requête pour tester le token et exploiter la variable d'environnement qui contient l'URL du backoffice
	```javascript
	import api from '@/axios';
	api.get(`/api/hello`)
	```
- Utiliser les *Skeleton* de primevue comme loader -> en faire des composants si nécessaire
