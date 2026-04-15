<script setup>
import { computed } from 'vue';
import { hasPermission } from '@utils/permissions';
import { useUsersStore } from '@stores';

/**
 * Component for conditional rendering based on permissions
 *
 * This component renders its content only if the current user has the required permissions.
 * It provides an alternative to the v-permission directive for more complex scenarios.
 */

const props = defineProps({
  /**
   * The permission(s) required to render the content
   * Can be a string, an array of strings, or an object with permissions and requireAll properties
   */
  permission: {
    type: [String, Array, Object, Boolean],
    required: true
  },

  /**
   * If true, renders the fallback slot when permission is denied
   * If false, renders nothing when permission is denied
   */
  showFallback: {
    type: Boolean,
    default: false
  }
});

const userStore = useUsersStore();

// Determine if the user has the required permission(s)
const hasRequiredPermission = computed(() => {
  // Accéder explicitement aux propriétés réactives du store qui affectent les permissions
  // pour garantir que le computed se recalcule quand ces valeurs changent.
  // Ceci est nécessaire car hasPermission est une fonction externe qui accède au store
  // mais Vue ne peut pas toujours tracker les dépendances à travers les appels de fonction.
  // eslint-disable-next-line no-unused-vars
  const _temporaryRole = userStore.temporaryRole;
  // eslint-disable-next-line no-unused-vars
  const _isLoaded = userStore.isLoaded;
  // eslint-disable-next-line no-unused-vars
  const _user = userStore.user;

  // Délègue entièrement à hasPermission qui gère:
  // - string
  // - array
  // - objet composite { permissions, requireAll }
  // - objet contextuel { permission, context }
  // - prédicat fonctionnel
  return hasPermission(props.permission);
});
</script>

<template>
  <template v-if="hasRequiredPermission">
    <!-- Render default slot when user has permission -->
    <slot></slot>
  </template>
  <template v-else-if="showFallback">
    <!-- Render fallback slot when user doesn't have permission -->
    <slot name="fallback">
      <!-- Default fallback content if no fallback slot is provided -->
      <div class="permission-denied">
        <p>Vous n'avez pas les droits nécessaires pour accéder à ce contenu.</p>
      </div>
    </slot>
  </template>
</template>

<style scoped>
.permission-denied {
  padding: 1rem;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  color: #6c757d;
  text-align: center;
}
</style>
