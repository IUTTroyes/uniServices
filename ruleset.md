# Guide suprême de l'Itranet V4

## Backend - SF7

- 
- 

## Frontend - VueJs3

- L'ordre doit être script, puis template et styles dans un fichier .vue
- Les composants doivent être en PascalCase
- Les méthodes doivent être en camelCase
- Utiliser l'intercepteur axios pour chaque requête pour tester le token et exploiter la variable d'environnement qui contient l'URL du backoffice
	```javascript
	import api from '@/axios';
	api.get(`/api/hello`)
	```
- Utiliser les *Skeleton* de primevue comme loader -> en faire des composants si nécessaire

