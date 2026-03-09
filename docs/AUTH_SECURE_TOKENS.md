# Authentification JWT Sécurisée avec Cookies HTTP-Only

## Vue d'ensemble

Cette implémentation utilise des cookies HTTP-only pour stocker les tokens JWT, ce qui offre une meilleure sécurité contre les attaques XSS par rapport au stockage dans localStorage.

## Mesures de sécurité implémentées

### Tokens JWT (Access Token)
- **Durée de vie** : 15 minutes (courte pour limiter les risques)
- **Stockage** : Cookie HTTP-only (inaccessible en JavaScript)
- **Extraction** : Depuis le cookie ou le header Authorization

### Refresh Token
- **Durée de vie** : 14 jours
- **Rotation** : Nouveau token à chaque utilisation (`single_use: true`)
- **Stockage** : Cookie HTTP-only
- **Révocation** : Supprimé de la BDD à la déconnexion

### Reset Token (Réinitialisation mot de passe)
- **Durée de vie** : 24 heures
- **Stockage en BDD** : Hash SHA-256 (pas le token en clair)
- **Protection timing attack** : Comparaison avec `hash_equals()`
- **Usage unique** : Supprimé après utilisation
- **Un seul actif** : Les anciens tokens sont supprimés à chaque nouvelle demande
- **Rate limiting** : Max 3 demandes par IP par heure

## Architecture

### Backend (Symfony)

#### Fichiers modifiés/créés :

1. **`config/packages/lexik_jwt_authentication.yaml`**
   - Token TTL réduit à 15 minutes
   - Configuration des extracteurs pour lire le token depuis les cookies
   - Configuration des cookies HTTP-only

2. **`config/packages/gesdinet_jwt_refresh_token.yaml`**
   - Configuration du refresh token avec TTL de 30 jours
   - Cookies HTTP-only pour le refresh token

3. **`config/packages/security.yaml`**
   - Ajout du firewall pour le refresh token
   - Nouvelles règles d'accès public pour `/api/token/refresh` et `/api/logout`

4. **`src/Entity/RefreshToken.php`**
   - Entité pour stocker les refresh tokens en base de données

5. **`src/EventListener/AuthenticationSuccessListener.php`**
   - Génère et stocke le refresh token lors de la connexion
   - Crée les cookies HTTP-only pour le JWT et le refresh token

6. **`src/Controller/AuthController.php`**
   - `POST /api/logout` : Supprime les tokens et les cookies
   - `GET /api/auth/me` : Retourne les informations de l'utilisateur authentifié

### Frontend (Vue.js)

#### Fichiers modifiés/créés :

1. **`packages/common-helpers/axios.js`**
   - Configuration `withCredentials: true` pour envoyer les cookies
   - Intercepteur pour rafraîchir automatiquement le token expiré
   - File d'attente pour les requêtes pendant le refresh

2. **`packages/common-helpers/authService.js`**
   - Service pour récupérer les informations utilisateur depuis le serveur
   - Fonctions `getAuthenticatedUser()`, `isAuthenticated()`, `logout()`

3. **`packages/common-stores/user_stores/userStore.js`**
   - Refactorisé pour récupérer `userId` et `userType` depuis le serveur
   - Plus de dépendance au localStorage pour le token

4. **`apps/auth/src/views/LoginView.vue`**
   - Plus de stockage du token dans localStorage
   - Utilise `withCredentials: true` pour recevoir les cookies

5. **`vite.config.js`**
   - Proxy configuré pour `/api` vers le backend

## Flux d'authentification

### Connexion

```
1. User → POST /api/login (username, password)
2. Backend vérifie les credentials
3. Backend génère JWT (15 min) + Refresh Token (30 jours)
4. Backend définit les cookies HTTP-only :
   - BEARER: JWT token
   - refresh_token: Refresh token
5. Frontend redirige vers le portail
```

### Requête API authentifiée

```
1. Frontend fait une requête (cookies envoyés automatiquement)
2. Backend extrait le JWT du cookie BEARER
3. Backend valide le token et traite la requête
```

### Refresh automatique du token

```
1. Requête échoue avec 401 (token expiré)
2. Axios interceptor détecte l'erreur
3. POST /api/token/refresh (cookie refresh_token envoyé)
4. Backend génère un nouveau JWT
5. Nouveau cookie BEARER défini
6. Requête originale rejouée
```

### Déconnexion

```
1. POST /api/logout
2. Backend supprime le refresh token de la DB
3. Backend expire les cookies BEARER et refresh_token
4. Frontend redirige vers login
```

## Migration de la base de données

Exécutez la migration pour créer la table `refresh_tokens` :

```bash
cd back
php bin/console doctrine:migrations:migrate
```

## Configuration pour la production

⚠️ **Important** : Pour la production avec HTTPS, modifiez ces fichiers :

### `config/packages/lexik_jwt_authentication.yaml`
```yaml
set_cookies:
    BEARER:
        secure: true  # Changer de false à true
```

### `config/packages/gesdinet_jwt_refresh_token.yaml`
```yaml
cookie:
    secure: true  # Changer de false à true
```

### `src/EventListener/AuthenticationSuccessListener.php`
```php
->withSecure(true)  # Changer de false à true (2 endroits)
```

### `src/Controller/AuthController.php`
```php
->withSecure(true)  # Changer de false à true (2 endroits)
```

## Sécurité

### Avantages de cette approche :

1. **Protection XSS** : Les cookies HTTP-only ne sont pas accessibles via JavaScript
2. **CSRF mitigé** : SameSite=Lax protège contre les attaques CSRF basiques
3. **Refresh automatique** : L'utilisateur reste connecté sans intervention
4. **Token court** : Le JWT expire en 15 min, limitant la fenêtre d'attaque
5. **Révocation possible** : Les refresh tokens peuvent être invalidés côté serveur

### Points d'attention :

- Assurez-vous que le frontend et le backend sont sur le même domaine (ou sous-domaines)
- En production, utilisez HTTPS et `secure: true` pour les cookies
- Implémentez un mécanisme de révocation des refresh tokens si nécessaire


