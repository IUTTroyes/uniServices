/**
 * Form Validation Configuration
 *
 * This file contains validation rules and error messages for form inputs.
 * Each input type has specific validation rules and corresponding error messages.
 */

// Validation rules for different input types
export const validationRules = {
  // Email validation
  email: {
    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    message: "Veuillez entrer une adresse email valide"
  },

  // URL validation
  url: {
    pattern: /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$/,
    message: "Veuillez entrer une URL valide (ex: https://exemple.com)"
  },

  // Phone number validation (French format)
  phone: {
    pattern: /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/,
    message: "Veuillez entrer un numéro de téléphone valide"
  },

  // Postal code validation (French format)
  postalCode: {
    pattern: /^[0-9]{5}$/,
    message: "Veuillez entrer un code postal valide (5 chiffres)"
  },

  // Required field validation
  required: {
    validate: value => value !== null && value !== undefined && value !== '',
    message: "Ce champ est obligatoire"
  },

  // Minimum length validation
  minLength: (length) => ({
    validate: value => !value || value.length >= length,
    message: `Ce champ doit contenir au moins ${length} caractères`
  }),

  // Maximum length validation
  maxLength: (length) => ({
    validate: value => !value || value.length <= length,
    message: `Ce champ ne doit pas dépasser ${length} caractères`
  }),

  // Numeric validation
  numeric: {
    pattern: /^[0-9]+$/,
    message: "Ce champ doit contenir uniquement des chiffres"
  },

  // Alphanumeric validation
  alphanumeric: {
    pattern: /^[a-zA-Z0-9]+$/,
    message: "Ce champ doit contenir uniquement des lettres et des chiffres"
  },

  // Min numeric value validation
  minValue: (min) => ({
    validate: value => !value || parseFloat(value) >= min,
    message: `La valeur doit être supérieure ou égale à ${min}`
  }),

  // Max numeric value validation
  maxValue: (max) => ({
    validate: value => !value || parseFloat(value) <= max,
    message: `La valeur doit être inférieure ou égale à ${max}`
  }),

  // Match validation (for password confirmation, etc.)
  match: (reference, errorMessage = "Les valeurs ne correspondent pas") => ({
    validate: value => value === reference,
    message: errorMessage
  })
};

/**
 * Validates a value against a validation rule
 * @param {*} value - The value to validate
 * @param {Object} rule - The validation rule
 * @returns {boolean} - Whether the value is valid
 */
export const validateValue = (value, rule) => {
  if (rule.pattern) {
    return rule.pattern.test(value);
  }
  if (rule.validate) {
    return rule.validate(value);
  }
  return true;
};

/**
 * Validates a form field value against multiple validation rules
 * @param {*} value - The value to validate
 * @param {Array|Object} rules - The validation rules to apply
 * @returns {Object} - Validation result with isValid and errorMessage properties
 */
export const validateField = (value, rules) => {
  if (!rules) {
    return { isValid: true, errorMessage: null };
  }

  // Convert single rule to array
  const ruleArray = Array.isArray(rules) ? rules : [rules];

  // Check if the field is empty
  const isEmpty = value === null || value === undefined || value === '';

  // Check if the field is required
  const isRequired = ruleArray.some(rule =>
      rule === validationRules.required ||
      (typeof rule === 'string' && rule === 'required') ||
      (rule && rule.validate && rule.validate.toString() === validationRules.required.validate.toString())
  );

  // If the field is empty and not required, skip validation
  if (isEmpty && !isRequired) {
    return { isValid: true, errorMessage: null };
  }

  for (const rule of ruleArray) {
    const isValid = validateValue(value, rule);
    if (!isValid) {
      return { isValid, errorMessage: rule.message };
    }
  }

  return { isValid: true, errorMessage: null };
};
