<template>
  <div class="action-dropdown-container">
    <Button
        :label="!iconOnly ? buttonLabel : undefined"
        :icon="iconOnly ? buttonIcon : undefined"
        :severity="buttonSeverity"
        :class="['action-button', sizeClass, { 'p-button-text': textOnly }]"
        @click="toggleDropdown"
        :disabled="disabled"
    />
    <Menu
        ref="menu"
        :model="menuItems"
        :popup="true"
        :class="menuClass"
        :appendTo="appendTo"
    />
  </div>
</template>

<script setup>
//https://dev.to/ahmadasroni38/buttondropdown-vuejs-and-primevue-1mc8
import { computed, ref } from 'vue'
import Button from 'primevue/button'
import Menu from 'primevue/menu'

const props = defineProps({
  // Action items configuration
  actions: {
    type: Array,
    default: () => [],
    validator: (items) => {
      return items.every((item) => {
        // Either has command/to/url, or is a separator
        return (
            item.separator ||
            (item.label && (item.command || item.to || item.url))
        )
      })
    },
  },

  // Button appearance
  buttonLabel: {
    type: String,
    default: null,
  },
  buttonIcon: {
    type: String,
    default: 'pi pi-ellipsis-v',
  },
  buttonSeverity: {
    type: String,
    default: 'secondary',
    validator: (value) =>
        [
          'primary',
          'secondary',
          'success',
          'info',
          'warn',
          'danger',
          'help',
        ].includes(value),
  },
  textOnly: {
    type: Boolean,
    default: false,
  },
  contextValue: {
    type: [Object, String, Number],
    default: null,
  },
  iconOnly: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },

  // Size and layout
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value),
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },

  // Menu configuration
  menuClass: {
    type: String,
    default: '',
  },
  appendTo: {
    type: String,
    default: 'body',
  },
  menuAlignment: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value),
  },
})

const emit = defineEmits(['action'])

const menu = ref(null)

// Create stable menu items without reactive dependencies
const menuItems = computed(() => {
  return props.actions.map((action) => {
    if (action.separator) {
      return { separator: true }
    }

    return {
      label: action.label,
      icon: action.icon,
      class: action.class,
      disabled: action.disabled,
      command: () => {
        emit('action', action, props.contextValue)
        if (action.command) {
          action.command(props.contextValue)
        }
      },
    }
  })
})

const sizeClass = computed(() => {
  return {
    small: 'p-button-sm',
    medium: '',
    large: 'p-button-lg',
  }[props.size]
})

const toggleDropdown = (event) => {
  if (props.disabled) return
  menu.value.toggle(event)
}
</script>

<style scoped>
.action-dropdown-container {
  @apply relative inline-flex;
}

.action-button {
  @apply flex items-center justify-center gap-2;

  &.p-button-text {
    @apply shadow-none;
  }
}

/* Full width variant */
.full-width {
  @apply w-full;

  .action-button {
    @apply w-full;
  }
}
</style>

<style>
/* Menu styling - using global style to override PrimeVue defaults */
.action-dropdown-container .p-menu {
  @apply min-w-[10rem] shadow-lg rounded-md border border-gray-200 mt-1 z-50;

  .p-menuitem {
    @apply hover:bg-gray-50;

    &.p-disabled {
      @apply opacity-50 cursor-not-allowed;
    }
  }

  .p-menuitem-link {
    @apply px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-50;

    .p-menuitem-icon {
      @apply text-gray-500 mr-2;
    }

    .p-menuitem-text {
      @apply flex-grow;
    }
  }

  .p-menu-separator {
    @apply border-t border-gray-200 my-1;
  }

  /* Danger items styling */

  .danger-item {
    @apply text-red-600 hover:bg-red-50;

    .p-menuitem-icon {
      @apply text-red-500;
    }
  }
}

/* Right-aligned menu */
.menu-right .p-menu {
  @apply right-0 left-auto;
}

/* Size variants */
.action-dropdown-container.small .p-menu {
  .p-menuitem-link {
    @apply px-3 py-1.5 text-xs;
  }
}

.action-dropdown-container.large .p-menu {
  .p-menuitem-link {
    @apply px-5 py-3 text-base;
  }
}
</style>
