# Système de Validation de Formulaires

Ce système permet de gérer de manière centralisée la validation des formulaires dans l'application. Il fournit des composants réutilisables et des règles de validation prédéfinies pour faciliter la validation des entrées utilisateur.

## Composants

### FormValidator

Composant de base qui gère la validation d'un champ de formulaire. Il prend en charge les propriétés suivantes:
- `model-value`: La valeur à valider
- `rules`: Les règles de validation à appliquer
- `validate-on-input`: Si la validation doit être effectuée à chaque modification de la valeur
- `validate-on-blur`: Si la validation doit être effectuée lorsque le champ perd le focus

### ValidatedInput

Composant prêt à l'emploi qui combine un champ de texte avec la validation. Il prend en charge toutes les propriétés de FormValidator, plus:
- `name`: Le nom du champ
- `label`: Le libellé du champ
- `placeholder`: Le texte d'exemple dans le champ
- `type`: Le type de champ (text, email, password, etc.)
- `help-text`: Un texte d'aide affiché sous le champ

### ExampleValidatedForm

Un exemple complet de formulaire utilisant le système de validation. Vous pouvez l'utiliser comme référence pour implémenter vos propres formulaires validés.

### AddressAutocomplete

Composant d'autocomplétion d'adresses intégré au `ValidatedInput`. Il permet aux utilisateurs de rechercher et sélectionner des adresses à partir de suggestions en temps réel.

#### Caractéristiques
- ✅ Recherche d'adresses en temps réel via **Géoplateforme (IGN)** - service français officiel
- ✅ Reconnaissance précise des numéros de rue (ex: "9 rue de québec troyes")
- ✅ Affichage lisible des suggestions avec adresse, code postal et ville
- ✅ Remplissage automatique des champs d'adresse
- ✅ Support du géocodage inversé (latitude/longitude)
- ✅ Gratuit, sans clé API requise
- ✅ Optimisé pour les adresses françaises

#### Utilisation

```vue
<ValidatedInput
  v-model="etablissement.adresse"
  name="adresse"
  label="Adresse"
  type="address"
  :rules="[]"
  placeholder="Entrez une adresse (minimum 3 caractères)"
  help-text="Recherchez et sélectionnez l'adresse"
  country="fr"
  class="w-full"
  @validation="result => handleValidation('adresse', result)"
/>
```

#### Structure des données

L'objet adresse renvoyé a la structure suivante:
```javascript
{
  adresse: "12 Rue de l'Exemple",      // Numéro et nom de la rue
  complement1: "",                     // Information supplémentaire (optionnel)
  complement2: "",                     // Information supplémentaire (optionnel)
  ville: "Paris",                      // Ville
  codePostal: "75001",                 // Code postal
  pays: "France"                       // Pays
}
```

#### Props spécifiques

- `type="address"`: Active le mode autocomplétion d'adresses
- `country` (String, défaut: 'fr'): Code ISO du pays pour filtrer les recherches
- `placeholder` (String): Texte d'aide (recommandé: "Entrez une adresse (minimum 3 caractères)")

#### Événements

- `@update:modelValue`: Émis quand une adresse est sélectionnée
- `@validation`: Émis avec le résultat de la validation
- `@blur`: Émis quand le champ perd le focus
- `@select`: (AddressAutocomplete) Émis avec les détails complets incluant les coordonnées GPS

#### Service d'adresses

Le composant utilise le service `addressService` qui fournit accès aux APIs de **Géoplateforme (IGN)**:

**searchAddresses(query, countryCode = 'fr')**
- Recherche des adresses via le service RNS (Recherche Nominative Simple) de Géoplateforme
- Minimum 3 caractères requis
- Excellent pour reconnaître les numéros de rue précisément
- Retourne un tableau de suggestions avec adresse, code postal, ville et coordonnées GPS

**reverseGeocode(lat, lon)**
- Effectue un géocodage inversé via le service de géocodage de Géoplateforme
- Utile pour convertir des coordonnées GPS en adresse
- Retourne les détails de l'adresse trouvée

Pour plus de détails, consultez [ADDRESS_AUTOCOMPLETE.md](./ADDRESS_AUTOCOMPLETE.md).

## Règles de Validation

Le système inclut plusieurs règles de validation prédéfinies dans le fichier `utils/formValidation.js` :

- `email` : Valide les adresses email
- `url` : Valide les URLs
- `phone` : Valide les numéros de téléphone (format français)
- `postalCode` : Valide les codes postaux (format français)
- `required` : Vérifie qu'un champ est rempli
- `minLength` : Vérifie la longueur minimale
- `maxLength` : Vérifie la longueur maximale
- `numeric` : Vérifie que le champ contient uniquement des chiffres
- `alphanumeric` : Vérifie que le champ contient uniquement des lettres et des chiffres

## Comment Utiliser

### 1. Importer les composants et les règles

```javascript
import { ValidatedInput, validationRules } from '@/packages/common-components';
```

### 2. Utiliser dans votre template

Utilisez le composant ValidatedInput dans votre template en lui passant les propriétés nécessaires:
- v-model pour lier la valeur
- name pour identifier le champ
- label pour afficher un libellé
- type pour spécifier le type de champ
- rules pour spécifier les règles de validation
- @validation pour gérer les événements de validation

#### Types de champs disponibles

Le composant `ValidatedInput` supporte les types suivants:

- **text** (défaut): Champ de texte simple
- **number**: Champ numérique avec validation
- **email**: Email avec validation
- **password**: Champ mot de passe avec masquage optionnel
- **select**: Liste déroulante
- **multiselect**: Sélection multiple
- **date**: Sélecteur de date
- **textarea**: Zone de texte multiligne
- **radio**: Bouton radio
- **file**: Upload de fichier
- **address**: Autocomplétion d'adresses (voir section AddressAutocomplete)

Exemples:

```vue
<!-- Texte -->
<ValidatedInput v-model="nom" type="text" label="Nom" />

<!-- Email -->
<ValidatedInput v-model="email" type="email" label="Email" :rules="[validationRules.required, validationRules.email]" />

<!-- Numéro -->
<ValidatedInput v-model="age" type="number" label="Âge" min="0" max="120" />

<!-- Date -->
<ValidatedInput v-model="dateNaissance" type="date" label="Date de naissance" />

<!-- Adresse avec autocomplétion -->
<ValidatedInput v-model="adresse" type="address" label="Adresse" country="fr" />
```

### 3. Gérer la validation

```javascript
const formErrors = ref({});
const formValid = ref(true);

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };

  // Vérifier si tous les champs sont valides
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};
```

### 4. Utiliser plusieurs règles

Vous pouvez combiner plusieurs règles de validation en passant un tableau de noms de règles:
```javascript
// Exemple: rules="['required', 'email']"
```

### 5. Créer des règles personnalisées

Vous pouvez créer vos propres règles de validation:

```javascript
const customRule = {
  validate: value => value.startsWith('ABC'),
  message: 'La valeur doit commencer par ABC'
};
```

## Extension du Système

Pour ajouter de nouvelles règles de validation, modifiez le fichier `utils/formValidation.js` et ajoutez vos règles à l'objet `validationRules`.
