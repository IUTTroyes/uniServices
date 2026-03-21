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
- rules pour spécifier les règles de validation
- @validation pour gérer les événements de validation

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
